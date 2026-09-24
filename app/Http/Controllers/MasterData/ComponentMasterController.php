<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\MasterData\ComponentMaster;
use Illuminate\Http\Request;

class ComponentMasterController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $components = ComponentMaster::when($search !== '', function ($q) use ($search) {
            $q->where(fn ($qq) => $qq->where('component_code', 'ilike', "%{$search}%")->orWhere('component_name', 'ilike', "%{$search}%"));
        })->orderBy('component_code')->paginate(15)->withQueryString();

        return view('master.components.index', compact('components', 'search'));
    }

    public function create()
    {
        return view('master.components.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'component_code' => 'required|string|max:50|unique:wms_component_masters,component_code',
            'component_name' => 'required|string|max:150',
            'default_uom' => 'required|string|max:20',
            'qty_label' => 'required|numeric|min:0',
        ]);

        ComponentMaster::create($data);

        return redirect()->route('master.components.index')->with('status', "Component \"{$data['component_code']}\" was added successfully.");
    }

    public function edit(ComponentMaster $component)
    {
        return view('master.components.edit', compact('component'));
    }

    public function update(Request $request, ComponentMaster $component)
    {
        $data = $request->validate([
            'component_code' => 'required|string|max:50|unique:wms_component_masters,component_code,'.$component->id,
            'component_name' => 'required|string|max:150',
            'default_uom' => 'required|string|max:20',
            'qty_label' => 'required|numeric|min:0',
        ]);

        $component->update($data);

        return redirect()->route('master.components.index')->with('status', "Component \"{$component->component_code}\" was updated successfully.");
    }

    public function print(Request $request)
    {
        $ids = $request->input('ids', []);

        $components = ComponentMaster::whereIn('id', $ids)->orderBy('component_code')->get();

        if ($components->isEmpty()) {
            return back()->withErrors(['ids' => 'Select at least one component to print.']);
        }

        $pdf = \Pdf::loadView('master.components.print', compact('components'));

        return $pdf->download('component-list.pdf');
    }
}
