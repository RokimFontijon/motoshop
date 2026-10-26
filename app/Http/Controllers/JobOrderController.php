<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\JobOrderPart;
use App\Models\Motorcycle;
use App\Models\Part;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JobOrderController extends Controller
{
    private function rules(): array
    {
        return [
            'motorcycle_id' => ['required', 'exists:motorcycles,id'],
            'mechanic_id' => ['nullable', 'exists:users,id'],
            'service_date' => ['required', 'date', 'before_or_equal:today'],
            'odometer_at_service' => ['required', 'integer', 'min:0', 'max:999999'],
            'complaint' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(JobOrder::STATUSES)],
            'labor_cost' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', 'exists:service_types,id'],
        ];
    }

    private function formData(): array
    {
        return [
            'motorcycles' => Motorcycle::with(['customer', 'motorcycleModel'])->orderBy('plate_number')->get(),
            'mechanics' => User::with('role')->orderBy('name')->get(),
            'serviceTypes' => ServiceType::orderBy('name')->get(),
            'statuses' => JobOrder::STATUSES,
        ];
    }

    /** Stops a mechanic from opening or changing someone else's job order. */
    private function ensureCanEdit(Request $request, JobOrder $jobOrder): void
    {
        abort_unless(
            $jobOrder->canBeEditedBy($request->user()),
            403,
            'This job order belongs to another mechanic, or it was already released.'
        );
    }

    /**
     * The odometer of a motorcycle should never go backwards. Returns an error
     * message when the new reading is lower than an earlier one, otherwise null.
     */
    private function odometerProblem(array $data, ?JobOrder $ignore = null): ?string
    {
        $earlierReading = JobOrder::where('motorcycle_id', $data['motorcycle_id'])
            ->whereDate('service_date', '<=', $data['service_date'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->max('odometer_at_service');

        if ($earlierReading !== null && $data['odometer_at_service'] < $earlierReading) {
            return 'This motorcycle already has an earlier reading of '.number_format($earlierReading).' km. The odometer cannot go backwards.';
        }

        return null;
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        // Eloquent joined with the database view that calculates each job order's total.
        $jobOrders = JobOrder::query()
            ->select('job_orders.*', 'totals.grand_total')
            ->join('v_job_order_totals as totals', 'totals.job_order_id', '=', 'job_orders.id')
            ->with(['motorcycle.customer', 'motorcycle.motorcycleModel', 'mechanic'])
            ->when(in_array($status, JobOrder::STATUSES, true), fn ($query) => $query->where('job_orders.status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('job_orders.job_number', 'ilike', "%{$search}%")
                        ->orWhereHas('motorcycle', fn ($q) => $q->where('plate_number', 'ilike', "%{$search}%"))
                        ->orWhereHas('motorcycle.customer', fn ($q) => $q->where('full_name', 'ilike', "%{$search}%"));
                });
            })
            ->orderByDesc('job_orders.service_date')
            ->orderByDesc('job_orders.id')
            ->paginate(10)
            ->withQueryString();

        return view('job_orders.index', [
            'jobOrders' => $jobOrders,
            'search' => $search,
            'status' => $status,
            'statuses' => JobOrder::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $motorcycle = Motorcycle::find($request->query('motorcycle_id'));

        $jobOrder = new JobOrder([
            'motorcycle_id' => $motorcycle?->id,
            'odometer_at_service' => $motorcycle?->current_odometer,
            'service_date' => now()->toDateString(),
            'status' => 'pending',
            'labor_cost' => 0,
            'mechanic_id' => $request->user()->id,
        ]);

        return view('job_orders.create', ['jobOrder' => $jobOrder, 'selectedServices' => []] + $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        if ($problem = $this->odometerProblem($data)) {
            return back()->withInput()->withErrors(['odometer_at_service' => $problem]);
        }

        // A mechanic always owns the job orders they create. An admin may pick the mechanic.
        $user = $request->user();
        $data['mechanic_id'] = $user->isAdmin() ? ($data['mechanic_id'] ?? $user->id) : $user->id;

        $jobOrder = DB::transaction(function () use ($data) {
            $jobOrder = JobOrder::create(Arr::except($data, 'service_type_ids'));
            $jobOrder->update(['job_number' => sprintf('JO-%05d', $jobOrder->id)]);
            $jobOrder->serviceTypes()->sync($data['service_type_ids'] ?? []);

            // The motorcycle's current odometer follows the newest reading.
            Motorcycle::whereKey($data['motorcycle_id'])
                ->where('current_odometer', '<', $data['odometer_at_service'])
                ->update(['current_odometer' => $data['odometer_at_service']]);

            return $jobOrder;
        });

        return redirect()->route('job-orders.show', $jobOrder)->with('status', 'Job order created. You can add parts now.');
    }

    public function show(JobOrder $jobOrder)
    {
        $jobOrder->load(['motorcycle.customer', 'motorcycle.motorcycleModel', 'mechanic', 'serviceTypes', 'partLines.part']);

        return view('job_orders.show', [
            'jobOrder' => $jobOrder,
            'availableParts' => Part::where('stock_qty', '>', 0)->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, JobOrder $jobOrder)
    {
        $this->ensureCanEdit($request, $jobOrder);

        return view('job_orders.edit', [
            'jobOrder' => $jobOrder,
            'selectedServices' => $jobOrder->serviceTypes()->pluck('service_types.id')->all(),
        ] + $this->formData());
    }

    public function update(Request $request, JobOrder $jobOrder): RedirectResponse
    {
        $this->ensureCanEdit($request, $jobOrder);

        $data = $request->validate($this->rules());

        if ($problem = $this->odometerProblem($data, $jobOrder)) {
            return back()->withInput()->withErrors(['odometer_at_service' => $problem]);
        }

        // Only an admin may move a job order to another mechanic.
        if (! $request->user()->isAdmin()) {
            unset($data['mechanic_id']);
        }

        DB::transaction(function () use ($jobOrder, $data) {
            $jobOrder->update(Arr::except($data, 'service_type_ids'));
            $jobOrder->serviceTypes()->sync($data['service_type_ids'] ?? []);

            Motorcycle::whereKey($jobOrder->motorcycle_id)
                ->where('current_odometer', '<', $data['odometer_at_service'])
                ->update(['current_odometer' => $data['odometer_at_service']]);
        });

        return redirect()->route('job-orders.show', $jobOrder)->with('status', 'Job order updated.');
    }

    /** Admin only (see routes/web.php). The parts used are given back to stock by the database trigger. */
    public function destroy(JobOrder $jobOrder): RedirectResponse
    {
        $jobOrder->delete();

        return redirect()->route('job-orders.index')->with('status', 'Job order deleted. Its parts were returned to stock.');
    }

    /** Adds a spare part to the job order. The database trigger lowers the stock. */
    public function addPart(Request $request, JobOrder $jobOrder): RedirectResponse
    {
        $this->ensureCanEdit($request, $jobOrder);

        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $part = Part::findOrFail($data['part_id']);

        if ($part->stock_qty < $data['quantity']) {
            return back()->withInput()->withErrors([
                'quantity' => "Only {$part->stock_qty} of {$part->name} left in stock.",
            ]);
        }

        DB::transaction(function () use ($jobOrder, $part, $data) {
            $line = $jobOrder->partLines()->where('part_id', $part->id)->first();

            if ($line) {
                // Same part again: add to the existing line instead of making a second one.
                $line->update(['quantity' => $line->quantity + $data['quantity']]);
            } else {
                // The price is copied now, so later price changes do not alter this job order.
                $jobOrder->partLines()->create([
                    'part_id' => $part->id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $part->unit_price,
                ]);
            }
        });

        return back()->with('status', "Added {$data['quantity']} x {$part->name}.");
    }

    /** Removes a part line. The database trigger returns the quantity to stock. */
    public function removePart(Request $request, JobOrder $jobOrder, JobOrderPart $line): RedirectResponse
    {
        $this->ensureCanEdit($request, $jobOrder);
        abort_unless($line->job_order_id === $jobOrder->id, 404);

        $line->delete();

        return back()->with('status', 'Part removed and returned to stock.');
    }
}
