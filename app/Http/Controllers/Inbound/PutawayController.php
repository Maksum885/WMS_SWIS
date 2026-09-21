<?php

namespace App\Http\Controllers\Inbound;

use App\Http\Controllers\Controller;
use App\Models\Inbound\Grn;
use App\Models\Inbound\Putaway;
use App\Services\RackTrackingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PutawayController extends Controller
{
    public function __construct(private RackTrackingService $rack) {}

    public function index(Request $request)
    {
        $putaways = Putaway::with('grn.supplier')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('inbound.putaways.index', compact('putaways'));
    }

    /** ?grn_id=X (opsional) — prefill dari GRN yang masih ada sisa qty belum ditaruh. */
    public function create(Request $request)
    {
        // Cuma slot yang benar-benar EMPTY saat ini (live dari m_matrix_storage_bin) —
        // supaya staf tidak bisa "konfirmasi" naruh barang di slot yang sudah terisi.
        $locationCodes = $this->rack->emptyLocationCodes();
        $selectedGrn = null;
        $prefillLines = [];

        if ($request->filled('grn_id')) {
            $selectedGrn = Grn::with('lines.putawayLines')->find($request->query('grn_id'));
            if ($selectedGrn) {
                $prefillLines = $selectedGrn->lines
                    ->map(fn ($l) => [
                        'grn_line_id' => $l->id,
                        'component' => $l->component,
                        'component_name' => $l->component_name,
                        'qty_received' => $l->qty_received,
                        'qty_put_away' => $l->qty_put_away,
                        'qty_outstanding' => max(0, (float) $l->qty_received - $l->qty_put_away),
                        'lot_no' => $l->lot_no,
                    ])
                    ->filter(fn ($l) => $l['qty_outstanding'] > 0)
                    ->values();
            }
        }

        // GRN yang masih punya sisa outstanding (belum sepenuhnya di-putaway).
        $openGrns = Grn::with('lines.putawayLines')
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($grn) => $grn->lines->sum(fn ($l) => max(0, (float) $l->qty_received - $l->qty_put_away)) > 0)
            ->values();

        $nextNo = 'PA-'.now()->format('ymd').'-'.str_pad((string) (Putaway::whereDate('created_at', now())->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('inbound.putaways.create', compact('locationCodes', 'selectedGrn', 'prefillLines', 'openGrns', 'nextNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'putaway_no' => 'required|string|max:50|unique:wms_putaways,putaway_no',
            'grn_id' => 'required|exists:wms_grns,id',
            'putaway_date' => 'required|date',
            'created_by' => 'nullable|string|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.grn_line_id' => 'required|exists:wms_grn_lines,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
            // Harus slot yang benar-benar EMPTY saat ini (dicek ulang di sini, bukan
            // cuma di create(), supaya tidak race dengan slot yang baru saja terisi
            // antara form dibuka dan disubmit).
            'lines.*.location_code' => ['required', 'string', Rule::in($this->rack->emptyLocationCodes())],
        ]);

        $putaway = Putaway::create([
            'putaway_no' => $data['putaway_no'],
            'grn_id' => $data['grn_id'],
            'putaway_date' => $data['putaway_date'],
            'status' => 'completed',
            'created_by' => $data['created_by'] ?? null,
        ]);

        foreach ($data['lines'] as $line) {
            $putaway->lines()->create([
                'grn_line_id' => $line['grn_line_id'],
                'qty' => $line['qty'],
                'location_code' => $line['location_code'],
                'confirmed_at' => now(),
                'confirmed_by' => $data['created_by'] ?? null,
            ]);
        }

        return redirect()->route('inbound.putaways.show', $putaway)->with('status', "Put Away \"{$putaway->putaway_no}\" was recorded successfully.");
    }

    public function show(Putaway $putaway)
    {
        $putaway->load(['grn.supplier', 'lines.grnLine']);

        return view('inbound.putaways.show', compact('putaway'));
    }

    public function exportPdf(Putaway $putaway)
    {
        $putaway->load(['grn.supplier', 'lines.grnLine']);

        $pdf = \Pdf::loadView('inbound.putaways.pdf', compact('putaway'));

        return $pdf->download("{$putaway->putaway_no}.pdf");
    }
}
