@extends('layouts.app')
@section('title', $grn->grn_no)

@section('content')
    @php
        $hasOutstanding = $grn->lines->sum(fn ($l) => max(0, (float) $l->qty_received - $l->qty_put_away)) > 0;
    @endphp

    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <a href="{{ route('inbound.grns.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title mono">{{ $grn->grn_no }}</div>
                <div class="report-panel-caption">Goods Receive Note</div>
            </div>
            <span class="status-tag status-{{ $grn->status }}">{{ ucfirst($grn->status) }}</span>
        </div>

        <div class="detail-grid">
            <div class="detail-field">
                <label>Supplier</label>
                <div class="val">{{ $grn->supplier->name ?? '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Source ASN</label>
                <div class="val">
                    @if ($grn->asn)
                        <a href="{{ route('inbound.asns.show', $grn->asn) }}" class="mono">{{ $grn->asn->asn_no }}</a>
                    @else
                        — (manual entry)
                    @endif
                </div>
            </div>
            <div class="detail-field">
                <label>Received Date</label>
                <div class="val">{{ optional($grn->received_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Transport Mode</label>
                <div class="val">{{ $grn->transport_mode ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Vehicle No</label>
                <div class="val mono">{{ $grn->vehicle_no ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Created</label>
                <div class="val">{{ $grn->created_at->format('d M Y H:i') }}</div>
            </div>
            <div class="detail-field" style="grid-column:1/-1;">
                <label>Remark</label>
                <div class="val">{{ $grn->remark ?: '—' }}</div>
            </div>
        </div>

        <div class="detail-actions">
            @if ($hasOutstanding)
                <a href="{{ route('inbound.putaways.create', ['grn_id' => $grn->id]) }}" class="btn-primary">Put Away from this GRN</a>
            @endif
            <a href="{{ route('inbound.grns.pdf', $grn) }}" class="btn-secondary">Download PDF</a>
        </div>
    </div>

    <div class="section-title">Line Items</div>
    <div class="table-wrap section-gap">
        <table>
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Lot No</th>
                    <th>Expired</th>
                    <th class="ta-right">Qty Received</th>
                    <th class="ta-right">Qty Put Away</th>
                    <th class="ta-right">Outstanding</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grn->lines as $l)
                    @php $outstanding = max(0, (float) $l->qty_received - $l->qty_put_away); @endphp
                    <tr>
                        <td>
                            <span class="mono">{{ $l->component }}</span>
                            <span class="muted-sub">{{ $l->component_name }}</span>
                        </td>
                        <td class="mono">{{ $l->lot_no ?: '—' }}</td>
                        <td class="mono">{{ optional($l->expired_date)->format('d M Y') ?: '—' }}</td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_received) }} {{ $l->uom }}</td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_put_away) }} {{ $l->uom }}</td>
                        <td class="ta-right mono {{ $outstanding > 0 ? 'qty-out' : 'qty-in' }}">{{ fmt_qty($outstanding) }} {{ $l->uom }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Put Away History</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Put Away No</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grn->putaways as $p)
                    <tr>
                        <td class="mono">{{ $p->putaway_no }}</td>
                        <td class="mono">{{ optional($p->putaway_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('inbound.putaways.show', $p) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No Put Away recorded from this GRN yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
