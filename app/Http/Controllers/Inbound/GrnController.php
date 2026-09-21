<?php

namespace App\Http\Controllers\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Inbound\Asn;
use App\Models\Inbound\Grn;
use App\Models\Inbound\Supplier;
use App\Services\RackTrackingService;
use Illuminate\Http\Request;

class GrnController extends Controller
{
    public function __construct(private RackTrackingService $rack) {}

    public function index(Request $request)
    {
        $grns = Grn::with(['supplier', 'asn'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('inbound.grns.index', compact('grns'));
    }

    /** ?asn_id=X (opsional) — prefill dari ASN yang masih ada sisa qty belum diterima. */
    public function create(Request $request)
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $selectedAsn = null;
        $prefillLines = [];

        if ($request->filled('asn_id')) {
            $selectedAsn = Asn::with('lines')->find($request->query('asn_id'));
            if ($selectedAsn) {
                $prefillLines = $selectedAsn->lines
                    ->map(fn ($l) => [
                        'component' => $l->component,
                        'component_name' => $l->component_name,
                        'qty' => $l->qty_remaining,
                        'uom' => $l->uom,
                    ])
                    ->filter(fn ($l) => $l['qty'] > 0)
                    ->values();
            }
        }

        $openAsns = Asn::where('status', 'confirmed')->orderByDesc('id')->get();
        $knownComponents = $this->rack->knownComponents();
        $nextNo = 'GRN-'.now()->format('ymd').'-'.str_pad((string) (Grn::whereDate('created_at', now())->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('inbound.grns.create', compact('suppliers', 'selectedAsn', 'prefillLines', 'openAsns', 'knownComponents', 'nextNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grn_no' => 'required|string|max:50|unique:wms_grns,grn_no',
            'asn_id' => 'nullable|exists:wms_asns,id',
            'supplier_id' => 'required|exists:wms_suppliers,id',
            'received_date' => 'required|date',
            'transport_mode' => 'nullable|string|max:20',
            'vehicle_no' => 'nullable|string|max:50',
            'remark' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component' => 'required|string|max:50',
            'lines.*.component_name' => 'nullable|string|max:150',
            'lines.*.qty_received' => 'required|numeric|min:0.01',
            'lines.*.uom' => 'required|string|max:20',
            'lines.*.lot_no' => 'nullable|string|max:50',
            'lines.*.mfg_date' => 'nullable|date',
            'lines.*.expired_date' => 'nullable|date',
        ]);

        $grn = Grn::create([
            'grn_no' => $data['grn_no'],
            'asn_id' => $data['asn_id'] ?? null,
            'supplier_id' => $data['supplier_id'],
            'received_date' => $data['received_date'],
            'transport_mode' => $data['transport_mode'] ?? null,
            'vehicle_no' => $data['vehicle_no'] ?? null,
            'status' => 'completed',
            'remark' => $data['remark'] ?? null,
        ]);

        foreach ($data['lines'] as $line) {
            $line['status'] = 'A';
            $grn->lines()->create($line);
        }

        return redirect()->route('inbound.grns.show', $grn)->with('status', "GRN \"{$grn->grn_no}\" was recorded successfully.");
    }

    public function show(Grn $grn)
    {
        $grn->load(['supplier', 'asn', 'lines.putawayLines', 'putaways']);

        return view('inbound.grns.show', compact('grn'));
    }

    public function exportPdf(Grn $grn)
    {
        $grn->load(['supplier', 'asn', 'lines']);

        $pdf = \Pdf::loadView('inbound.grns.pdf', compact('grn'));

        return $pdf->download("{$grn->grn_no}.pdf");
    }
}
