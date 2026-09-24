<?php

namespace App\Http\Controllers\Outbound;

use App\Http\Controllers\Controller;
use App\Models\Outbound\Customer;
use App\Models\Outbound\SalesOrder;
use App\Services\RackTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function __construct(private RackTrackingService $rack) {}

    public function index(Request $request)
    {
        $status = $request->query('status');

        $salesOrders = SalesOrder::with('customer')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('outbound.sales-orders.index', compact('salesOrders', 'status'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        // Sumbernya stok riil (warehouse_stock), BUKAN matrix_partcode lagi —
        // tabel itu tidak pernah terisi lewat jalur manapun di operasional nyata.
        // Cuma barang yang benar-benar ada stoknya yang bisa dijual.
        $sellableItems = $this->rack->stockPerItem();
        $nextNo = 'SO-'.now()->format('ymd').'-'.str_pad((string) (SalesOrder::whereDate('created_at', now())->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('outbound.sales-orders.create', compact('customers', 'sellableItems', 'nextNo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'so_no' => 'required|string|max:50|unique:wms_sales_orders,so_no',
            'customer_id' => 'required|exists:wms_customers,id',
            'order_date' => 'required|date',
            'required_date' => 'nullable|date',
            'remark' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component' => 'required|string|max:50|exists:warehouse_stock,component',
            'lines.*.qty_ordered' => 'required|numeric|min:0.01',
            'lines.*.uom' => 'required|string|max:20',
            'lines.*.remark' => 'nullable|string|max:255',
        ]);

        $salesOrder = SalesOrder::create([
            'so_no' => $data['so_no'],
            'customer_id' => $data['customer_id'],
            'order_date' => $data['order_date'],
            'required_date' => $data['required_date'] ?? null,
            'status' => 'draft',
            'remark' => $data['remark'] ?? null,
        ]);

        // component_name diambil dari stok riil (bukan input client), supaya nama
        // yang tersimpan selalu konsisten dengan warehouse_stock.
        $names = $this->rack->stockPerItem()->keyBy('component')->map(fn ($i) => $i->component_name);

        foreach ($data['lines'] as $line) {
            $salesOrder->lines()->create([
                'component' => $line['component'],
                'component_name' => $names[$line['component']] ?? $line['component'],
                'qty_ordered' => $line['qty_ordered'],
                'uom' => $line['uom'],
                'remark' => $line['remark'] ?? null,
            ]);
        }

        return redirect()->route('outbound.sales-orders.show', $salesOrder)->with('status', "Sales Order \"{$salesOrder->so_no}\" was created successfully.");
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'lines', 'pickings', 'deliveryOrders']);

        return view('outbound.sales-orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return redirect()->route('outbound.sales-orders.show', $salesOrder)->withErrors(['status' => 'Only a draft Sales Order can be edited.']);
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $sellableItems = $this->rack->stockPerItem();
        $salesOrder->load('lines');

        // Baris yang sudah ada di SO ini tapi komponennya sekarang stoknya 0
        // (mis. data lama) tetap dimasukkan ke pilihan, supaya edit tidak diam-diam
        // menghilangkan baris itu — cuma ditandai "0 in stock", bukan disembunyikan.
        $existingComponents = $salesOrder->lines->pluck('component')->unique();
        $missing = $existingComponents->diff($sellableItems->pluck('component'))
            ->map(fn ($code) => (object) [
                'component' => $code,
                'component_name' => $salesOrder->lines->firstWhere('component', $code)->component_name,
                'unit' => $salesOrder->lines->firstWhere('component', $code)->uom,
                'qty_total' => 0,
            ]);
        $sellableItems = $sellableItems->concat($missing)->sortBy('component')->values();

        return view('outbound.sales-orders.edit', compact('salesOrder', 'customers', 'sellableItems'));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return redirect()->route('outbound.sales-orders.show', $salesOrder)->withErrors(['status' => 'Only a draft Sales Order can be edited.']);
        }

        // Komponen yang sudah dipakai di baris SO ini sebelumnya (biar tetap boleh
        // disimpan ulang meski stoknya sekarang 0 — bukan berarti harus dihapus
        // cuma karena diedit).
        $grandfatheredNames = $salesOrder->lines->pluck('component_name', 'component');
        $grandfatheredComponents = $grandfatheredNames->keys();

        $data = $request->validate([
            'so_no' => 'required|string|max:50|unique:wms_sales_orders,so_no,'.$salesOrder->id,
            'customer_id' => 'required|exists:wms_customers,id',
            'order_date' => 'required|date',
            'required_date' => 'nullable|date',
            'remark' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.component' => [
                'required', 'string', 'max:50',
                // Boleh tetap komponen yang sudah dipakai di baris SO ini sebelumnya
                // (grandfathered) walau stoknya sekarang 0 — selain itu wajib ada
                // stoknya.
                function (string $attribute, $value, \Closure $fail) use ($grandfatheredComponents) {
                    if ($grandfatheredComponents->contains($value)) {
                        return;
                    }
                    if (! DB::table('warehouse_stock')->where('component', $value)->exists()) {
                        $fail('The selected component is not currently in stock.');
                    }
                },
            ],
            'lines.*.qty_ordered' => 'required|numeric|min:0.01',
            'lines.*.uom' => 'required|string|max:20',
            'lines.*.remark' => 'nullable|string|max:255',
        ]);

        $salesOrder->update([
            'so_no' => $data['so_no'],
            'customer_id' => $data['customer_id'],
            'order_date' => $data['order_date'],
            'required_date' => $data['required_date'] ?? null,
            'remark' => $data['remark'] ?? null,
        ]);

        $names = $this->rack->stockPerItem()->keyBy('component')->map(fn ($i) => $i->component_name)
            ->union($grandfatheredNames);

        // Draft belum punya Picking sama sekali (baru bisa dibuat Picking setelah
        // confirm), jadi aman ganti seluruh baris daripada diff satu-satu.
        $salesOrder->lines()->delete();
        foreach ($data['lines'] as $line) {
            $salesOrder->lines()->create([
                'component' => $line['component'],
                'component_name' => $names[$line['component']] ?? $line['component'],
                'qty_ordered' => $line['qty_ordered'],
                'uom' => $line['uom'],
                'remark' => $line['remark'] ?? null,
            ]);
        }

        return redirect()->route('outbound.sales-orders.show', $salesOrder)->with('status', "Sales Order \"{$salesOrder->so_no}\" was updated successfully.");
    }

    public function exportPdf(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'lines']);

        $pdf = \Pdf::loadView('outbound.sales-orders.pdf', compact('salesOrder'));

        return $pdf->download("{$salesOrder->so_no}.pdf");
    }

    public function confirm(SalesOrder $salesOrder)
    {
        if ($salesOrder->status === 'draft') {
            $salesOrder->update(['status' => 'confirmed']);

            return back()->with('status', "Sales Order \"{$salesOrder->so_no}\" has been confirmed.");
        }

        return back()->withErrors(['status' => 'This Sales Order is no longer in draft status.']);
    }
}
