<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /** Validation rules shared by store() and update(). */
    private function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->withCount('motorcycles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('full_name', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('customers.create', ['customer' => new Customer()]);
    }

    public function store(Request $request): RedirectResponse
    {
        // create() only accepts the columns listed in $fillable (mass assignment protection).
        $customer = Customer::create($request->validate($this->rules()));

        return redirect()->route('customers.show', $customer)->with('status', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $customer->load('motorcycles.motorcycleModel');

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validate($this->rules()));

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    /** Admin only (see routes/web.php). */
    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->motorcycles()->exists()) {
            return back()->with('error', 'This customer still has motorcycles. Remove or reassign them first.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer deleted.');
    }
}
