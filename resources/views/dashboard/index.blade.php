@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    @php
        $fmtDelta = fn (int $d) => ($d > 0 ? '+' : '').$d;
        $inDelta = $cards['box_in_today'] - $cards['box_in_yesterday'];
        $outDelta = $cards['box_out_today'] - $cards['box_out_yesterday'];
    @endphp

    <div class="cards-row">
        <a href="{{ route('laporan.index') }}" class="card card-link card-accent-blue">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line></svg></div>
            <div class="card-label">TOTAL ITEMS REGISTERED</div>
            <div class="card-value" id="cardTotalItem">{{ number_format($cards['total_item']) }}</div>
        </a>
        <a href="{{ route('laporan.index', ['tab' => 'stok']) }}" class="card card-link card-accent-purple">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg></div>
            <div class="card-label">ITEMS RUNNING LOW</div>
            @if ($lowestItem)
                <div class="card-value" id="cardLowestItemQty">{{ number_format($lowestItem->qty_total) }} {{ $lowestItem->unit }}</div>
                <div class="card-sub" id="cardLowestItemName">{{ $lowestItem->component_name }} &middot; {{ $lowestItem->component }}</div>
            @else
                <div class="card-value">&mdash;</div>
                <div class="card-sub">No items in stock yet</div>
            @endif
        </a>
        <a href="{{ route('laporan.index') }}" class="card card-link card-accent-green">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"></rect><rect x="14" y="3" width="7" height="5" rx="1.5"></rect><rect x="14" y="12" width="7" height="9" rx="1.5"></rect><rect x="3" y="16" width="7" height="5" rx="1.5"></rect></svg></div>
            <div class="card-label">SLOTS FILLED</div>
            <div class="card-value"><span id="cardSlotTerisi">{{ $cards['slot_terisi'] }}</span> / {{ $cards['total_slot'] }}</div>
            <div class="card-sub"><span id="cardOkupansi">{{ $cards['okupansi_pct'] }}</span>% occupancy</div>
        </a>
        <a href="{{ route('transaksi', ['dari' => now()->toDateString(), 'sampai' => now()->toDateString()]) }}" class="card card-link card-accent-red">
            <div class="card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15.5 14"></polyline></svg></div>
            <div class="card-label">BOXES IN/OUT TODAY</div>
            <div class="card-value">
                <span class="stat-in" id="cardBoxIn">{{ $cards['box_in_today'] }}</span>
                <span class="stat-sep">/</span>
                <span class="stat-out" id="cardBoxOut">{{ $cards['box_out_today'] }}</span>
            </div>
            <div class="card-sub"><span class="stat-in">in</span> &middot; <span class="stat-out">out</span></div>
            <div class="card-sub" id="cardBoxDelta">vs yesterday: {{ $fmtDelta($inDelta) }} in &middot; {{ $fmtDelta($outDelta) }} out</div>
        </a>
    </div>

    <div class="panel">
        <div class="panel-dark-head">
            <div class="panel-dark-head-left">
                <span class="panel-dark-head-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><path d="M12 17h.01"></path></svg></span>
                <span class="panel-dark-head-title">WMS Activity</span>
            </div>
        </div>
        <div class="activity-list" id="wmsActivityList">
            @forelse ($wmsActivity as $row)
                <a href="{{ $row['url'] }}" class="activity-row">
                    <span class="activity-badge">{{ $row['count'] }}</span>
                    <span class="activity-label">{{ $row['label'] }}</span>
                    <span class="activity-arrow">&rarr;</span>
                </a>
            @empty
                <div class="activity-empty">All caught up — no pending WMS actions.</div>
            @endforelse
        </div>
    </div>

    <div class="panels-row">
        <div class="panel">
            <div class="panel-dark-head">
                <div class="panel-dark-head-left">
                    <span class="panel-dark-head-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg></span>
                    <span class="panel-dark-head-title">Utilization per rack</span>
                </div>
            </div>
            <div class="panel-caption">Number of filled slots out of 45 per rack, live from current slot status. Click a bar to open that rack.</div>
            <canvas id="chartRack" height="130"></canvas>
        </div>
        <div class="panel">
            <div class="panel-dark-head">
                <div class="panel-dark-head-left">
                    <span class="panel-dark-head-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 17 9 11 13 15 21 7"></polyline><polyline points="14 7 21 7 21 14"></polyline></svg></span>
                    <span class="panel-dark-head-title">In / out trend</span>
                </div>
                <div class="range-pills no-print">
                    @foreach ([7, 14, 30] as $d)
                        <a href="{{ route('dashboard', ['days' => $d]) }}" class="range-pill-dark {{ $days === $d ? 'active' : '' }}">{{ $d }}D</a>
                    @endforeach
                </div>
            </div>
            <div class="panel-caption">Number of distinct boxes moved in &amp; out per day, calculated from the transaction ledger.</div>
            <canvas id="chartTrend" height="130"></canvas>
        </div>
    </div>

    <div class="panel">
        <div class="panel-dark-head">
            <div class="panel-dark-head-left">
                <span class="panel-dark-head-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg></span>
                <span class="panel-dark-head-title">Item Overview</span>
            </div>
        </div>
        <div class="panel-caption">
            Current stock &amp; number of movements per item over the last {{ $days }} days, most active first
            @if ($cards['total_item'] > $itemOverview->count())
                &middot; showing top {{ $itemOverview->count() }} of {{ $cards['total_item'] }} items
            @endif
            &middot; <a href="{{ route('laporan.index', ['tab' => 'stok']) }}">view full stock report &rarr;</a>
        </div>
        @if ($itemOverview->isNotEmpty())
            <div class="item-overview-header" id="itemOverviewHeader">
                <span>Item</span>
                <span class="ta-right">Current Qty</span>
                <span class="ta-right">Movements ({{ $days }}D)</span>
            </div>
        @endif
        <div class="item-overview-list" id="itemOverviewList">
            @forelse ($itemOverview as $item)
                <div class="item-overview-row">
                    <span>{{ $item->component_name }}<span class="muted-sub">{{ $item->component }}</span></span>
                    <span class="ta-right mono">{{ number_format($item->qty_total) }} {{ $item->unit }}</span>
                    <span class="ta-right"><span class="item-move-badge mono">{{ $item->movement_count }}</span></span>
                </div>
            @empty
                <div class="table-empty-msg">No items yet.</div>
            @endforelse
        </div>
    </div>
@endsection

@push('scripts')
<script>
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.color = '#0F172A';

    const rackLabels = @json($utilization->pluck('rack'));
    const rackIndexUrl = "{{ route('rack.index') }}";
    const days = @json($days);

    const chartRack = new Chart(document.getElementById('chartRack'), {
        type: 'bar',
        data: { labels: rackLabels, datasets: [{ label: 'Slots filled', data: @json($utilization->pluck('filled')), backgroundColor: '#6366F1', borderRadius: 4, maxBarThickness: 28 }] },
        options: {
            plugins: { legend: { display: false } },
            onClick: (evt, elements) => {
                if (!elements.length) return;
                window.location.href = `${rackIndexUrl}?rack=${rackLabels[elements[0].index]}`;
            },
            onHover: (evt, elements) => { evt.native.target.style.cursor = elements.length ? 'pointer' : 'default'; },
            scales: {
                y: { min: 0, max: 45, ticks: { stepSize: 15, font: { family: 'Inter', size: 11.5 } }, grid: { color: '#E2E8F0' } },
                x: { ticks: { font: { family: 'Inter', size: 11.5 } }, grid: { display: false } }
            }
        }
    });

    const chartTrend = new Chart(document.getElementById('chartTrend'), {
        type: 'line',
        data: {
            labels: @json($trend['labels']),
            datasets: [
                { label: 'In', data: @json($trend['in']), borderColor: '#059669', backgroundColor: 'transparent', tension: 0.35, borderWidth: 2.5, pointRadius: 3, pointBackgroundColor: '#059669' },
                { label: 'Out', data: @json($trend['out']), borderColor: '#E11D48', backgroundColor: 'transparent', tension: 0.35, borderWidth: 2.5, pointRadius: 3, pointBackgroundColor: '#E11D48' }
            ]
        },
        options: {
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 12, family: 'Inter' } } } },
            scales: {
                y: {
                    beginAtZero: true, suggestedMax: 5,
                    ticks: { font: { size: 11.5, family: 'Inter' }, precision: 0, stepSize: 1 },
                    grid: { color: '#E2E8F0' },
                },
                x: { ticks: { font: { size: 11.5, family: 'Inter' } }, grid: { display: false } }
            }
        }
    });

    // Lightweight auto-refresh every 30 seconds — card numbers & chart data update in
    // place, without a page reload. This is what satisfies the original "realtime" goal;
    // the actual data writer (the WinForms app) writes straight to Postgres outside of
    // Laravel, so polling the DB is the most realistic approach (not websockets/broadcast).
    const fmt = n => Number(n).toLocaleString('en-US');
    const fmtDelta = n => (n > 0 ? '+' : '') + n;

    function renderWmsActivity(rows) {
        const list = document.getElementById('wmsActivityList');
        if (!list) return;
        if (!rows.length) {
            list.innerHTML = '<div class="activity-empty">All caught up — no pending WMS actions.</div>';
            return;
        }
        list.innerHTML = rows.map(row => `
            <a href="${row.url}" class="activity-row">
                <span class="activity-badge">${row.count}</span>
                <span class="activity-label">${row.label}</span>
                <span class="activity-arrow">&rarr;</span>
            </a>`).join('');
    }

    function renderItemOverview(items) {
        const list = document.getElementById('itemOverviewList');
        if (!list) return;
        if (!items.length) {
            list.innerHTML = '<div class="table-empty-msg">No items yet.</div>';
            return;
        }
        list.innerHTML = items.map(item => `
            <div class="item-overview-row">
                <span>${item.component_name}<span class="muted-sub">${item.component}</span></span>
                <span class="ta-right mono">${fmt(item.qty_total)} ${item.unit}</span>
                <span class="ta-right"><span class="item-move-badge mono">${item.movement_count}</span></span>
            </div>`).join('');
    }

    async function refreshDashboard() {
        try {
            const res = await fetch(`{{ route('dashboard.data') }}?days=${days}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const d = await res.json();

            document.getElementById('cardTotalItem').textContent = fmt(d.cards.total_item);
            document.getElementById('cardSlotTerisi').textContent = fmt(d.cards.slot_terisi);
            document.getElementById('cardOkupansi').textContent = d.cards.okupansi_pct;
            document.getElementById('cardBoxIn').textContent = fmt(d.cards.box_in_today);
            document.getElementById('cardBoxOut').textContent = fmt(d.cards.box_out_today);
            document.getElementById('cardBoxDelta').textContent =
                `vs yesterday: ${fmtDelta(d.cards.box_in_today - d.cards.box_in_yesterday)} in · ${fmtDelta(d.cards.box_out_today - d.cards.box_out_yesterday)} out`;

            const lowestQtyEl = document.getElementById('cardLowestItemQty');
            const lowestNameEl = document.getElementById('cardLowestItemName');
            if (d.lowest_item && lowestQtyEl && lowestNameEl) {
                lowestQtyEl.textContent = `${fmt(d.lowest_item.qty_total)} ${d.lowest_item.unit}`;
                lowestNameEl.textContent = `${d.lowest_item.component_name} · ${d.lowest_item.component}`;
            }

            chartRack.data.datasets[0].data = d.utilization.map(u => u.filled);
            chartRack.update();

            chartTrend.data.labels = d.trend.labels;
            chartTrend.data.datasets[0].data = d.trend.in;
            chartTrend.data.datasets[1].data = d.trend.out;
            chartTrend.update();

            renderItemOverview(d.item_overview);
            renderWmsActivity(d.wms_activity);
        } catch (e) { /* stay quiet, retry on the next interval */ }
    }
    setInterval(refreshDashboard, 30000);
</script>
@endpush
