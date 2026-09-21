<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $customers = Customer::when($search !== '', function ($q) use ($search) {
            $q->where(fn ($qq) => $qq->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%"));
        })->orderBy('name')->paginate(15)->withQueryString();

        return view('outbound.customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('outbound.customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:wms_customers,code',
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'contact_person' => 'nullable|string|max:150',
        ]);

        Customer::create($data);

        return redirect()->route('outbound.customers.index')->with('status', "Customer \"{$data['name']}\" was added successfully.");
    }

    public function edit(Customer $customer)
    {
        return view('outbound.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:wms_customers,code,'.$customer->id,
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'contact_person' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $customer->update($data);

        return redirect()->route('outbound.customers.index')->with('status', "Customer \"{$customer->name}\" was updated successfully.");
    }
}
