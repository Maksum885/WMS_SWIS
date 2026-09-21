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
