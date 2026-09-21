<?php

namespace App\Http\Controllers\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Inbound\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $suppliers = Supplier::when($search !== '', function ($q) use ($search) {
            $q->where(fn ($qq) => $qq->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%"));
        })->orderBy('name')->paginate(15)->withQueryString();

        return view('inbound.suppliers.index', compact('suppliers', 'search'));
    }

    public function create()
    {
        return view('inbound.suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:wms_suppliers,code',
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'contact_person' => 'nullable|string|max:150',
        ]);

        Supplier::create($data);

        return redirect()->route('inbound.suppliers.index')->with('status', "Supplier \"{$data['name']}\" was added successfully.");
    }

    public function edit(Supplier $supplier)
    {
        return view('inbound.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:wms_suppliers,code,'.$supplier->id,
            'name' => 'required|string|max:150',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'contact_person' => 'nullable|string|max:150',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $supplier->update($data);

        return redirect()->route('inbound.suppliers.index')->with('status', "Supplier \"{$supplier->name}\" was updated successfully.");
    }
}
