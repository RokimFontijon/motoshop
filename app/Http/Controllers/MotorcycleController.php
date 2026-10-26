<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Motorcycle;
use App\Models\MotorcycleModel;
use App\Services\ServiceDueReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MotorcycleController extends Controller
{
    private function rules(?Motorcycle $motorcycle = null): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'motorcycle_model_id' => ['required', 'exists:motorcycle_models,id'],
            'plate_number' => [
                'required', 'string', 'max:15',
                // The plate must be unique, but a motorcycle may keep its own plate when edited.
                Rule::unique('motorcycles', 'plate_number')->ignore($motorcycle?->id),
            ],
            'year' => ['nullable', 'integer', 'between:1980,2100'],
            'color' => ['nullable', 'string', 'max:30'],
            'current_odometer' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /** Plates are saved in capital letters without extra spaces ("nbx 4821 " becomes "NBX 4821"). */
    private function cleanedInput(Request $request): void
    {
        $request->merge(['plate_number' => strtoupper(trim((string) $request->input('plate_number')))]);
    }

    private function formData(): array
    {
        return [
            'customers' => Customer::orderBy('full_name')->get(),
            'models' => MotorcycleModel::orderBy('brand')->orderBy('name')->get(),
        ];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $motorcycles = Motorcycle::query()
            ->with(['customer', 'motorcycleModel'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('plate_number', 'ilike', "%{$search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('full_name', 'ilike', "%{$search}%"))
                        ->orWhereHas('motorcycleModel', fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                            ->orWhere('brand', 'ilike', "%{$search}%"));
                });
            })
            ->orderBy('plate_number')
            ->paginate(10)
            ->withQueryString();

        return view('motorcycles.index', compact('motorcycles', 'search'));
    }

    public function create(Request $request)
    {
        // /motorcycles/create?customer_id=3 pre-selects the owner.
        $motorcycle = new Motorcycle(['customer_id' => $request->query('customer_id')]);

        return view('motorcycles.create', ['motorcycle' => $motorcycle] + $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->cleanedInput($request);
        $motorcycle = Motorcycle::create($request->validate($this->rules()));

        return redirect()->route('motorcycles.show', $motorcycle)->with('status', 'Motorcycle added.');
    }

    public function show(Motorcycle $motorcycle, ServiceDueReport $report)
    {
        $motorcycle->load(['customer', 'motorcycleModel']);

        $history = $motorcycle->jobOrders()
            ->with(['serviceTypes', 'mechanic'])
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->get();

        return view('motorcycles.show', [
            'motorcycle' => $motorcycle,
            'history' => $history,
            'dueRows' => $report->dueRows($motorcycle->id),
        ]);
    }

    public function edit(Motorcycle $motorcycle)
    {
        return view('motorcycles.edit', ['motorcycle' => $motorcycle] + $this->formData());
    }

    public function update(Request $request, Motorcycle $motorcycle): RedirectResponse
    {
        $this->cleanedInput($request);
        $motorcycle->update($request->validate($this->rules($motorcycle)));

        return redirect()->route('motorcycles.show', $motorcycle)->with('status', 'Motorcycle updated.');
    }

    /** Admin only (see routes/web.php). */
    public function destroy(Motorcycle $motorcycle): RedirectResponse
    {
        if ($motorcycle->jobOrders()->exists()) {
            return back()->with('error', 'This motorcycle has job orders, so it cannot be deleted.');
        }

        $motorcycle->delete();

        return redirect()->route('motorcycles.index')->with('status', 'Motorcycle deleted.');
    }
}
