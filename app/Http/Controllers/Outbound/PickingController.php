<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\Picking;
use App\Models\Outbound\SalesOrder;
use App\Models\Outbound\SoLine;
use App\Services\RackTrackingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PickingController extends Controller
{
    public function __construct(private RackTrackingService $rack) {}

    public function index(Request $request)
    {
        $pickings = Picking::with('salesOrder.customer')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('outbound.pickings.index', compact('pickings'));
    }

    /** ?sales_order_id=X (opsional) — prefill dari SO confirmed yang masih ada sisa qty belum dipicking. */
    public function create(Request $request)
    {
        // Cuma slot yang FULL saat ini (live dari m_matrix_storage_bin) — masuk
        // akal cuma ambil barang dari slot yang memang ada isinya.
        $locationCodes = $this->rack->occupiedLocationCodes();
        $selectedSo = null;
        $prefillLines = [];

        if ($request->filled('sales_order_id')) {
            $selectedSo = SalesOrder::with('lines')->find($request->query('sales_order_id'));
            if ($selectedSo) {
                $prefillLines = $selectedSo->lines
                    ->map(fn ($l) => [
                        'so_line_id' => $l->id,
                        'partcode' => $l->component,
                        'model_name' => $l->component_name,
                        'qty_ordered' => $l->qty_ordered,
                        'qty_picked' => $l->qty_picked,
                        'qty_outstanding' => max(0, (float) $l->qty_ordered - $l->qty_picked),
                        'uom' => $l->uom,
                    ])
                    ->filter(fn ($l) => $l['qty_outstanding'] > 0)
                    ->values();
            }
        }

        // SO confirmed yang masih punya sisa outstanding (belum sepenuhnya dipicking).
        $openSalesOrders = SalesOrder::with('lines')
            ->where('status', 'confirmed')
            ->orderByDesc('id')
            ->get()
            ->filter(fn ($so) => $so->lines->sum(fn ($l) => max(0, (float) $l->qty_ordered - $l->qty_picked)) > 0)
            ->values();

        $nextNo = 'PK-'.now()->format('ymd').'-'.str_pad((string) (Picking::whereDate('created_at', now())->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('outbound.pickings.create', compact('locationCodes', 'selectedSo', 'prefillLines', 'openSalesOrders', 'nextNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'picking_no' => 'required|string|max:50|unique:wms_pickings,picking_no',
            'sales_order_id' => 'required|exists:wms_sales_orders,id',
            'picking_date' => 'required|date',
            'created_by' => 'nullable|string|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.so_line_id' => 'required|exists:wms_so_lines,id',
            'lines.*.qty_picked' => 'required|numeric|min:0.01',
            'lines.*.lot_no' => 'nullable|string|max:50',
            // Harus slot yang benar-benar FULL saat ini (dicek ulang di sini, bukan
            // cuma di create(), supaya tidak race dengan slot yang baru dikosongkan).
            'lines.*.location_code' => ['required', 'string', Rule::in($this->rack->occupiedLocationCodes())],
        ]);

        $picking = Picking::create([
            'picking_no' => $data['picking_no'],
            'sales_order_id' => $data['sales_order_id'],
            'picking_date' => $data['picking_date'],
            'status' => 'completed',
            'created_by' => $data['created_by'] ?? null,
        ]);

        foreach ($data['lines'] as $line) {
            $soLine = SoLine::findOrFail($line['so_line_id']);
            $picking->lines()->create([
                'so_line_id' => $soLine->id,
                'component' => $soLine->component,
                'component_name' => $soLine->component_name,
                'qty_picked' => $line['qty_picked'],
                'lot_no' => $line['lot_no'] ?? null,
                'location_code' => $line['location_code'],
                'confirmed_at' => now(),
                'confirmed_by' => $data['created_by'] ?? null,
            ]);
        }

        return redirect()->route('outbound.pickings.show', $picking)->with('status', "Picking \"{$picking->picking_no}\" was recorded successfully.");
    }

    public function show(Picking $picking)
    {
        $picking->load(['salesOrder.customer', 'lines.soLine', 'deliveryOrders']);

        return view('outbound.pickings.show', compact('picking'));
    }

    public function exportPdf(Picking $picking)
    {
        $picking->load(['salesOrder.customer', 'lines.soLine']);

        $pdf = \Pdf::loadView('outbound.pickings.pdf', compact('picking'));

        return $pdf->download("{$picking->picking_no}.pdf");
    }
}
