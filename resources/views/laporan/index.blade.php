@extends('layouts.app')
@section('title', 'Reports')

@section('content')
    @php
        $sortUrl = fn (string $col) => request()->fullUrlWithQuery([
            'sort' => $col,
            'dir' => ($sortBy === $col && $sortDir === 'asc') ? 'desc' : 'asc',
        ]);
        $tabUrl = fn (string $t) => request()->fullUrlWithQuery(['tab' => $t, 'page' => null]);
        $removeParam = fn (string $key) => route('laporan.index', collect(request()->query())->except([$key, 'page'])->all());
        $removePeriod = route('laporan.index', collect(request()->query())->except(['dari', 'sampai', 'page'])->all());
        $hasFilter = (bool) ($rackFilter || $search || $dateFrom || $dateTo);
        $tabLabel = ['stok' => 'Stock per Item', 'utilisasi' => 'Rack Utilization', 'mutasi' => 'Movement Summary', 'putaway' => 'Put Away Summary'][$activeTab];
    @endphp

    <div class="report-meta">
        <div>
            <div class="report-meta-title">Inventory Report</div>
            <div class="report-meta-sub">Auto-generated from live data &middot; {{ now()->translatedFormat('d F Y, H:i') }}</div>
        </div>
    </div>

    {{-- ===== Report tabs — only one shown at a time ===== --}}
    <div class="rack-tabs no-print">
        <a href="{{ $tabUrl('stok') }}" class="rack-tab {{ $activeTab === 'stok' ? 'active' : '' }}">A &middot; Stock per Item</a>
        <a href="{{ $tabUrl('utilisasi') }}" class="rack-tab {{ $activeTab === 'utilisasi' ? 'active' : '' }}">
            B &middot; Rack Utilization{{ $nearFullCount > 0 ? " ({$nearFullCount})" : '' }}
        </a>
        <a href="{{ $tabUrl('mutasi') }}" class="rack-tab {{ $activeTab === 'mutasi' ? 'active' : '' }}">C &middot; Movement Summary</a>
        <a href="{{ $tabUrl('putaway') }}" class="rack-tab rack-tab-warn {{ $activeTab === 'putaway' ? 'active' : '' }}">D &middot; Put Away Summary</a>
    </div>

    {{-- ===== One combined panel: filter (relevant to the active tab) + choose & download ===== --}}
    <div class="report-panel no-print">
        <form method="GET" action="{{ route('laporan.index') }}">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="hidden" name="sort" value="{{ $sortBy }}">
            <input type="hidden" name="dir" value="{{ $sortDir }}">

            <div class="filter-context-note">
                @switch($activeTab)
                    @case('stok') Filter by part code/item name and rack. @break
                    @case('utilisasi') Rack utilization is a live snapshot — no part/rack filter applies here. @break
                    @case('mutasi') Filter by part code/item name, rack, and a date period. @break
                    @case('putaway') Filter by part code/item name only — this report isn't broken down by rack. @break
                @endswitch
            </div>
            <div class="filters" style="margin-bottom:14px;">
                @unless ($activeTab === 'utilisasi')
                    <div class="filter-field">
                        <label for="f-q">Part Code / Item Name</label>
                        <input type="text" name="q" id="f-q" value="{{ $search }}" placeholder="e.g. B13" class="filter-date mono">
                    </div>
                @else
                    <input type="hidden" name="q" value="{{ $search }}">
                @endunless

                @unless ($activeTab === 'putaway')
                    <div class="filter-field">
                        <label for="f-rack">Rack</label>
                        <select name="rack" id="f-rack" class="filter-select">
                            <option value="">All racks</option>
                            @foreach ($racks as $r)
                                <option value="{{ $r }}" @selected($rackFilter === $r)>Rack {{ substr($r, 1) }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="rack" value="{{ $rackFilter }}">
                @endunless

                @if ($activeTab === 'mutasi')
                    <div class="filter-field">
                        <label for="f-dari">From</label>
                        <input type="date" name="dari" id="f-dari" value="{{ $dateFrom }}" class="filter-date">
                    </div>
                    <div class="filter-field">
                        <label for="f-sampai">To</label>
                        <input type="date" name="sampai" id="f-sampai" value="{{ $dateTo }}" class="filter-date">
                    </div>
                @else
                    <input type="hidden" name="dari" value="{{ $dateFrom }}">
                    <input type="hidden" name="sampai" value="{{ $dateTo }}">
                @endif

                <button type="submit" class="filter-btn filter-field-btn">Apply</button>
            </div>

            @if ($hasFilter)
                <div class="filter-chips" style="border-top:none; padding-top:0; margin-bottom:14px;">
                    @if ($rackFilter)
                        <a href="{{ $removeParam('rack') }}" class="search-chip local mono">Rack {{ substr($rackFilter, 1) }} <span class="chip-x">&times;</span></a>
                    @endif
                    @if ($search)
                        <a href="{{ $removeParam('q') }}" class="search-chip local mono">"{{ $search }}" <span class="chip-x">&times;</span></a>
                    @endif
                    @if ($dateFrom || $dateTo)
                        <a href="{{ $removePeriod }}" class="search-chip local mono">Period: {{ $dateFrom ?: 'start' }} &rarr; {{ $dateTo ?: 'now' }} <span class="chip-x">&times;</span></a>
                    @endif
                    <a href="{{ route('laporan.index', ['tab' => $activeTab]) }}" class="filter-clear-all">Clear all filters</a>
                </div>
            @endif

            <details class="export-sections">
                <summary class="export-sections-label">Include in download &mdash; defaults to the current tab, click to customize</summary>
                <div class="export-check-row">
                    <label class="export-check"><input type="checkbox" name="sections[]" value="stok" @checked(in_array('stok', $sections, true))> A &middot; Stock per Item</label>
                    <label class="export-check"><input type="checkbox" name="sections[]" value="utilisasi" @checked(in_array('utilisasi', $sections, true))> B &middot; Rack Utilization</label>
                    <label class="export-check"><input type="checkbox" name="sections[]" value="mutasi" @checked(in_array('mutasi', $sections, true))> C &middot; Movement Summary</label>
                    <label class="export-check"><input type="checkbox" name="sections[]" value="putaway" @checked(in_array('putaway', $sections, true))> D &middot; Put Away Summary</label>
                </div>
            </details>
            <div class="report-actions">
                <button type="submit" formaction="{{ route('laporan.export.excel') }}" class="btn-export">Download Excel</button>
                <button type="submit" formaction="{{ route('laporan.export.pdf') }}" class="btn-export">Download PDF</button>
            </div>
        </form>
    </div>

    {{-- ===== Report A: Stock per Item ===== --}}
    @if ($activeTab === 'stok')
        <div class="report-panel">
            <div class="report-panel-header">
                <div>
                    <div class="report-panel-title">Report A &middot; Stock per Item</div>
                    <div class="report-panel-caption">{{ $stockPerItem->count() }} items shown{{ ($rackFilter || $search) ? ' (filtered)' : '' }}.</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><a href="{{ $sortUrl('sku') }}" class="th-sort">Part Code</a></th>
                            <th><a href="{{ $sortUrl('nama') }}" class="th-sort">Item Name</a></th>
                            <th class="ta-right"><a href="{{ $sortUrl('qty') }}" class="th-sort">Total Qty</a></th>
                            <th>Spread Across Racks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stockPerItem as $item)
                            <tr>
                                <td>{{ $item->component }}</td>
                                <td>{{ $item->component_name }}</td>
                                <td class="ta-right mono">{{ number_format($item->qty_total) }} {{ $item->unit }}</td>
                                <td class="mono">{{ $item->racks->implode(', ') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="table-empty-msg">
                                {{ ($rackFilter || $search) ? 'No items match the filter.' : 'No data.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== Report B: Rack Utilization ===== --}}
    @if ($activeTab === 'utilisasi')
        <div class="report-panel">
            <div class="report-panel-header">
                <div>
                    <div class="report-panel-title">Report B &middot; Rack Utilization</div>
                    <div class="report-panel-caption">Live snapshot of current conditions &mdash; not yet available by date/period since historical occupancy logs aren't in the database.</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Rack</th><th>Slots Filled</th><th>Occupancy</th></tr></thead>
                    <tbody>
                        @foreach ($rackUtilization as $u)
                            <tr class="{{ $rackFilter === $u['rack'] ? 'row-active' : '' }}">
                                <td class="mono">{{ $u['rack'] }}</td>
                                <td class="mono">{{ $u['filled'] }} / {{ $u['capacity'] }}</td>
                                <td>
                                    <div class="occ-cell">
                                        <div class="occ-bar"><div class="occ-bar-fill {{ $u['pct'] >= 85 ? 'high' : ($u['pct'] >= 50 ? 'mid' : 'low') }}" style="width:{{ $u['pct'] }}%"></div></div>
                                        <span class="mono occ-pct">{{ $u['pct'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== Report C: Movement Summary ===== --}}
    @if ($activeTab === 'mutasi')
        <div class="report-panel">
            <div class="report-panel-header">
                <div>
                    <div class="report-panel-title">Report C &middot; Movement Summary</div>
                    <div class="report-panel-caption">
                        Total qty in &amp; out per item
                        @if ($dateFrom || $dateTo)
                            for the period {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') : 'start' }} &ndash; {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') : 'now' }}
                        @else
                            across the full transaction history
                        @endif
                        , calculated from the warehouse_stock ledger{{ $hasFilter ? ' (filtered)' : '' }}.
                    </div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><a href="{{ $sortUrl('sku') }}" class="th-sort">Part Code</a></th>
                            <th><a href="{{ $sortUrl('nama') }}" class="th-sort">Item Name</a></th>
                            <th class="ta-right"><a href="{{ $sortUrl('masuk') }}" class="th-sort">Total In</a></th>
                            <th class="ta-right"><a href="{{ $sortUrl('keluar') }}" class="th-sort">Total Out</a></th>
                            <th class="ta-right"><a href="{{ $sortUrl('net') }}" class="th-sort">Net</a></th>
                            <th class="ta-right"><a href="{{ $sortUrl('box') }}" class="th-sort">Box Count</a></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutationSummary as $m)
                            <tr>
                                <td>{{ $m->component }}</td>
                                <td>{{ $m->component_name }}</td>
                                <td class="ta-right mono qty-in">+{{ number_format($m->total_masuk) }} {{ $m->unit }}</td>
                                <td class="ta-right mono qty-out">-{{ number_format($m->total_keluar) }} {{ $m->unit }}</td>
                                <td class="ta-right mono">{{ $m->net > 0 ? '+' : '' }}{{ number_format($m->net) }} {{ $m->unit }}</td>
                                <td class="ta-right mono">{{ $m->jumlah_box }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="table-empty-msg">No movement in this period/filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== Report D: Put Away Summary ===== --}}
    @if ($activeTab === 'putaway')
        <div class="note-info">Not the true stock figure — see Report A &middot; Stock per Item for that. Only totals what has been recorded through ASN &rarr; GRN &rarr; Put Away in the WMS module.</div>
        <div class="report-panel">
            <div class="report-panel-header">
                <div>
                    <div class="report-panel-title">Report D &middot; Put Away Summary</div>
                    <div class="report-panel-caption">{{ $putAwaySummary->count() }} components shown{{ $search ? ' (filtered)' : '' }}.</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Part Code</th>
                            <th>Item Name</th>
                            <th class="ta-right">Qty Put Away (WMS)</th>
                            <th>By Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($putAwaySummary as $p)
                            <tr>
                                <td>{{ $p->component }}</td>
                                <td>{{ $p->component_name }}</td>
                                <td class="ta-right mono qty-in">{{ fmt_qty($p->qty_put_away) }} {{ $p->uom }}</td>
                                <td>
                                    @foreach ($putAwayByLocation->get($p->component, collect()) as $loc)
                                        <span class="tag" style="background:#eef2ff;color:#4338ca;margin:0 4px 4px 0;display:inline-block;">{{ $loc->location_code }}: {{ fmt_qty($loc->qty) }}</span>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="table-empty-msg">
                                {{ $search ? 'No components match the filter.' : 'No Put Away recorded yet.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
