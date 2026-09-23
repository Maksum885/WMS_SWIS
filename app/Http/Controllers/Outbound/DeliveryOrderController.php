<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\DeliveryOrder;
use App\Models\Outbound\Picking;
use App\Models\Outbound\SalesOrder;
use App\Services\RackTrackingService;
use Illuminate\Http\Request;

class DeliveryOrderController extends Controller
{
    public function __construct(private RackTrackingService $rack) {}

    public function index(Request $request)
    {
        $deliveryOrders = DeliveryOrder::with('salesOrder.customer')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('outbound.delivery-orders.index', compact('deliveryOrders'));
    }

    /** ?picking_id=X (opsional) — prefill dari Picking yang sudah selesai & belum ada DO. */
    public function create(Request $request)
    {
        $salesOrders = SalesOrder::whereIn('status', ['confirmed'])->orderByDesc('id')->get();
        // Sama seperti Sales Order (lihat SalesOrderController) — sumbernya stok
        // riil, bukan matrix_partcode, buat opsi baris manual (tanpa Picking).
        $sellableItems = $this->rack->stockPerItem();
        $selectedPicking = null;
        $prefillLines = [];

        if ($request->filled('picking_id')) {
            $selectedPicking = Picking::with(['salesOrder.customer', 'lines.soLine'])
                ->find($request->query('picking_id'));

            if ($selectedPicking) {
                $prefillLines = $selectedPicking->lines
                    ->groupBy(fn ($l) => $l->component.'|'.$l->lot_no)
                    ->map(function ($group) {
                        $first = $group->first();

                        return [
                            'partcode' => $first->component,
                            'model_name' => $first->component_name,
                            'qty' => $group->sum('qty_picked'),
                            'uom' => $first->soLine->uom ?? 'Pcs',
                            'lot_no' => $first->lot_no,
                        ];
                    })
                    ->values();
            }
        }

        // Picking selesai yang belum punya DO sama sekali.
        $openPickings = Picking::with('salesOrder.customer')
            ->where('status', 'completed')
            ->doesntHave('deliveryOrders')
            ->orderByDesc('id')
            ->get();

        $nextNo = 'DO-'.now()->format('ymd').'-'.str_pad((string) (DeliveryOrder::whereDate('created_at', now())->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('outbound.delivery-orders.create', compact('salesOrders', 'sellableItems', 'selectedPicking', 'prefillLines', 'openPickings', 'nextNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'do_no' => 'required|string|max:50|unique:wms_delivery_orders,do_no',
            'sales_order_id' => 'required|exists:wms_sales_orders,id',
            'picking_id' => 'nullable|exists:wms_pickings,id',
            'delivery_date' => 'required|date',
            'vehicle_no' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'remark' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component' => 'required|string|max:50|exists:warehouse_stock,component',
            'lines.*.qty_delivered' => 'required|numeric|min:0.01',
            'lines.*.uom' => 'required|string|max:20',
            'lines.*.lot_no' => 'nullable|string|max:50',
        ]);

        $deliveryOrder = DeliveryOrder::create([
            'do_no' => $data['do_no'],
            'sales_order_id' => $data['sales_order_id'],
            'picking_id' => $data['picking_id'] ?? null,
            'delivery_date' => $data['delivery_date'],
            'vehicle_no' => $data['vehicle_no'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'status' => 'shipped',
            'remark' => $data['remark'] ?? null,
        ]);

        $names = $this->rack->stockPerItem()->keyBy('component')->map(fn ($i) => $i->component_name);

        foreach ($data['lines'] as $line) {
            $deliveryOrder->lines()->create([
                'component' => $line['component'],
                'component_name' => $names[$line['component']] ?? $line['component'],
                'qty_delivered' => $line['qty_delivered'],
                'uom' => $line['uom'],
                'lot_no' => $line['lot_no'] ?? null,
            ]);
        }

        return redirect()->route('outbound.delivery-orders.show', $deliveryOrder)->with('status', "Delivery Order \"{$deliveryOrder->do_no}\" was recorded successfully.");
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['salesOrder.customer', 'picking', 'lines']);

        return view('outbound.delivery-orders.show', compact('deliveryOrder'));
    }

    public function exportPdf(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['salesOrder.customer', 'picking', 'lines']);

        $pdf = \Pdf::loadView('outbound.delivery-orders.pdf', compact('deliveryOrder'));

        return $pdf->download("{$deliveryOrder->do_no}.pdf");
    }
}
