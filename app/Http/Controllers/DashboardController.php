<?php

namespace App\Http\Controllers;

use App\Models\Inbound\Asn;
use App\Models\Inbound\Grn;
use App\Models\MasterData\ComponentMaster;
use App\Models\Outbound\SalesOrder;
use App\Services\RackTrackingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Rentang tren in/out yang bisa dipilih user. */
    private const TREND_DAYS = [7, 14, 30];

    public function __construct(private RackTrackingService $rack) {}

    public function index(Request $request)
    {
        $days = $this->readDays($request);

        $cards = $this->rack->dashboardCards();
        $utilization = $this->rack->rackUtilization();
        $trend = $this->rack->inOutTrend($days);
        $lowestItem = $this->rack->lowestItemByStock();
        $itemOverview = $this->rack->itemOverview($days);
        $wmsActivity = $this->wmsActivity();

        return view('dashboard.index', compact('cards', 'utilization', 'trend', 'lowestItem', 'itemOverview', 'days', 'wmsActivity'));
    }

    /** JSON: dipakai auto-refresh dashboard (polling) tanpa reload halaman. */
    public function data(Request $request)
    {
        $days = $this->readDays($request);

        return response()->json([
            'cards' => $this->rack->dashboardCards(),
            'utilization' => $this->rack->rackUtilization(),
            'trend' => $this->rack->inOutTrend($days),
            'lowest_item' => $this->rack->lowestItemByStock(),
            'item_overview' => $this->rack->itemOverview($days),
            'wms_activity' => $this->wmsActivity(),
        ]);
    }

    /**
     * Hal-hal WMS yang masih butuh tindak lanjut — bukan status snapshot
     * (beda dari 4 card lama yang murni angka rack/stok), supaya dashboard
     * juga jadi "apa yang perlu dikerjakan hari ini" untuk modul WMS
     * (Inbound/Outbound/Component Master), bukan cuma monitoring rack.
     */
    private function wmsActivity(): array
    {
        $asnDraft = Asn::where('status', 'draft')->count();

        // GRN yang masih ada baris dengan qty_received > qty sudah di-Put Away.
        $grnOutstanding = Grn::whereHas('lines', function ($q) {
            $q->whereRaw('qty_received > (select coalesce(sum(qty), 0) from wms_putaway_lines where wms_putaway_lines.grn_line_id = wms_grn_lines.id)');
        })->count();

        $soDraft = SalesOrder::where('status', 'draft')->count();

        // SO yang sudah dikonfirmasi tapi masih ada baris dengan qty_ordered > qty sudah dipicking.
        $soAwaitingPicking = SalesOrder::where('status', 'confirmed')
            ->whereHas('lines', function ($q) {
                $q->whereRaw('qty_ordered > (select coalesce(sum(qty_picked), 0) from wms_picking_lines where wms_picking_lines.so_line_id = wms_so_lines.id)');
            })->count();

        $componentNeedsQty = ComponentMaster::where('qty_label', 0)->count();

        $rows = [
            ['count' => $asnDraft, 'label' => 'ASN draft awaiting confirmation', 'url' => route('inbound.asns.index', ['status' => 'draft'])],
            ['count' => $grnOutstanding, 'label' => 'GRN with outstanding Put Away', 'url' => route('inbound.grns.index')],
            ['count' => $soDraft, 'label' => 'Sales Order draft awaiting confirmation', 'url' => route('outbound.sales-orders.index', ['status' => 'draft'])],
            ['count' => $soAwaitingPicking, 'label' => 'Sales Order awaiting Picking', 'url' => route('outbound.sales-orders.index', ['status' => 'confirmed'])],
            ['count' => $componentNeedsQty, 'label' => 'Component Master with Qty on Label not yet set', 'url' => route('master.components.index')],
        ];

        return array_values(array_filter($rows, fn ($r) => $r['count'] > 0));
    }

    private function readDays(Request $request): int
    {
        $days = (int) $request->query('days');

        return in_array($days, self::TREND_DAYS, true) ? $days : 7;
    }
}
