<?php

namespace App\Http\Controllers\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Inbound\Asn;
use App\Models\Inbound\Supplier;
use App\Models\MasterData\ComponentMaster;
use Illuminate\Http\Request;

class AsnController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $asns = Asn::with('supplier')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('inbound.asns.index', compact('asns', 'status'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        // Baris ASN WAJIB relasi ke Component Master (tidak bisa lagi ketik
        // bebas/auto-create komponen baru) — kalau komponennya belum ada,
        // harus didaftarkan dulu di menu Master Data > Component Master.
        $components = ComponentMaster::orderBy('component_code')->get();

        $nextNo = 'ASN-'.now()->format('ymd').'-'.str_pad((string) (Asn::whereDate('created_at', now())->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('inbound.asns.create', compact('suppliers', 'components', 'nextNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asn_no' => 'required|string|max:50|unique:wms_asns,asn_no',
            'po_number' => 'nullable|string|max:50',
            'supplier_id' => 'required|exists:wms_suppliers,id',
            'expected_date' => 'nullable|date',
            'remark' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component' => 'required|string|max:50|exists:wms_component_masters,component_code',
            'lines.*.qty_expected' => 'required|numeric|min:0.01',
            'lines.*.uom' => 'required|string|max:20',
        ]);

        $asn = Asn::create([
            'asn_no' => $data['asn_no'],
            'po_number' => $data['po_number'] ?? null,
            'supplier_id' => $data['supplier_id'],
            'expected_date' => $data['expected_date'] ?? null,
            'status' => 'draft',
            'remark' => $data['remark'] ?? null,
        ]);

        // component_name diambil dari Component Master (bukan dari input client)
        // supaya nama yang tersimpan selalu konsisten dengan master, sesuai kode
        // yang dipilih di dropdown.
        $names = ComponentMaster::whereIn('component_code', collect($data['lines'])->pluck('component'))
            ->pluck('component_name', 'component_code');

        foreach ($data['lines'] as $line) {
            $asn->lines()->create([
                'component' => $line['component'],
                'component_name' => $names[$line['component']] ?? $line['component'],
                'qty_expected' => $line['qty_expected'],
                'uom' => $line['uom'],
            ]);
        }

        return redirect()->route('inbound.asns.show', $asn)->with('status', "ASN \"{$asn->asn_no}\" was created successfully.");
    }

    public function show(Asn $asn)
    {
        $asn->load(['supplier', 'lines', 'grns.supplier']);

        return view('inbound.asns.show', compact('asn'));
    }

    public function edit(Asn $asn)
    {
        if ($asn->status !== 'draft') {
            return redirect()->route('inbound.asns.show', $asn)->withErrors(['status' => 'Only a draft ASN can be edited.']);
        }

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $components = ComponentMaster::orderBy('component_code')->get();
        $asn->load('lines');

        // Baris yang sudah ada di ASN ini tapi kodenya sekarang tidak (lagi) ada di
        // Component Master (mis. dibuat sebelum Component Master wajib, atau
        // masternya sudah dihapus) tetap dimasukkan ke pilihan, supaya edit tidak
        // diam-diam kehilangan baris itu.
        $existingComponents = $asn->lines->pluck('component')->unique();
        $missing = $existingComponents->diff($components->pluck('component_code'))
            ->map(fn ($code) => (object) [
                'component_code' => $code,
                'component_name' => $asn->lines->firstWhere('component', $code)->component_name,
                'default_uom' => $asn->lines->firstWhere('component', $code)->uom,
            ]);
        $components = $components->concat($missing)->sortBy('component_code')->values();

        return view('inbound.asns.edit', compact('asn', 'suppliers', 'components'));
    }

    public function update(Request $request, Asn $asn)
    {
        if ($asn->status !== 'draft') {
            return redirect()->route('inbound.asns.show', $asn)->withErrors(['status' => 'Only a draft ASN can be edited.']);
        }

        // Komponen yang sudah dipakai di baris ASN ini sebelumnya (biar tetap boleh
        // disimpan ulang meski sekarang tidak ada di Component Master).
        $grandfatheredNames = $asn->lines->pluck('component_name', 'component');
        $grandfatheredComponents = $grandfatheredNames->keys();

        $data = $request->validate([
            'asn_no' => 'required|string|max:50|unique:wms_asns,asn_no,'.$asn->id,
            'po_number' => 'nullable|string|max:50',
            'supplier_id' => 'required|exists:wms_suppliers,id',
            'expected_date' => 'nullable|date',
            'remark' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component' => [
                'required', 'string', 'max:50',
                function (string $attribute, $value, \Closure $fail) use ($grandfatheredComponents) {
                    if ($grandfatheredComponents->contains($value)) {
                        return;
                    }
                    if (! ComponentMaster::where('component_code', $value)->exists()) {
                        $fail('The selected component is not registered in Component Master.');
                    }
                },
            ],
            'lines.*.qty_expected' => 'required|numeric|min:0.01',
            'lines.*.uom' => 'required|string|max:20',
        ]);

        $asn->update([
            'asn_no' => $data['asn_no'],
            'po_number' => $data['po_number'] ?? null,
            'supplier_id' => $data['supplier_id'],
            'expected_date' => $data['expected_date'] ?? null,
            'remark' => $data['remark'] ?? null,
        ]);

        $names = ComponentMaster::whereIn('component_code', collect($data['lines'])->pluck('component'))
            ->pluck('component_name', 'component_code')
            ->union($grandfatheredNames);

        // Draft belum punya GRN sama sekali (baru bisa dibuat GRN setelah confirm),
        // jadi aman ganti seluruh baris daripada diff satu-satu.
        $asn->lines()->delete();
        foreach ($data['lines'] as $line) {
            $asn->lines()->create([
                'component' => $line['component'],
                'component_name' => $names[$line['component']] ?? $line['component'],
                'qty_expected' => $line['qty_expected'],
                'uom' => $line['uom'],
            ]);
        }

        return redirect()->route('inbound.asns.show', $asn)->with('status', "ASN \"{$asn->asn_no}\" was updated successfully.");
    }

    public function exportPdf(Asn $asn)
    {
        $asn->load(['supplier', 'lines']);

        $pdf = \Pdf::loadView('inbound.asns.pdf', compact('asn'));

        return $pdf->download("{$asn->asn_no}.pdf");
    }

    public function confirm(Asn $asn)
    {
        if ($asn->status === 'draft') {
            $asn->update(['status' => 'confirmed']);

            return back()->with('status', "ASN \"{$asn->asn_no}\" has been confirmed.");
        }

        return back()->withErrors(['status' => 'This ASN is no longer in draft status.']);
    }
}
