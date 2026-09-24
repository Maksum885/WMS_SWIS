@extends('layouts.app')
@section('title', $asn->asn_no)

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="note-danger">{{ $errors->first() }}</div>
    @endif

    <a href="{{ route('inbound.asns.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title mono">{{ $asn->asn_no }}</div>
                <div class="report-panel-caption">Advance Shipment Notice</div>
            </div>
            <span class="status-tag status-{{ $asn->status }}">{{ ucfirst($asn->status) }}</span>
        </div>

        <div class="detail-grid">
            <div class="detail-field">
                <label>Supplier</label>
                <div class="val">{{ $asn->supplier->name ?? '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Expected Date</label>
                <div class="val">{{ optional($asn->expected_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>PO Number</label>
                <div class="val mono">{{ $asn->po_number ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Created</label>
                <div class="val">{{ $asn->created_at->format('d M Y H:i') }}</div>
            </div>
            <div class="detail-field span-3" style="grid-column:1/-1;">
                <label>Remark</label>
                <div class="val">{{ $asn->remark ?: '—' }}</div>
            </div>
        </div>

        <div class="detail-actions">
            @if ($asn->status === 'draft')
                <form method="POST" action="{{ route('inbound.asns.confirm', $asn) }}" onsubmit="return confirm('Confirm this ASN? Once confirmed it can be used to create a GRN.');">
                    @csrf
                    <button type="submit" class="btn-primary">Confirm ASN</button>
                </form>
                <a href="{{ route('inbound.asns.edit', $asn) }}" class="btn-secondary">Edit ASN</a>
            @else
                <a href="{{ route('inbound.grns.create', ['asn_id' => $asn->id]) }}" class="btn-primary">Create GRN from this ASN</a>
            @endif
            <a href="{{ route('inbound.asns.pdf', $asn) }}" class="btn-secondary">Download PDF</a>
        </div>
    </div>

    <div class="section-title">Line Items</div>
    <div class="table-wrap section-gap">
        <table>
            <thead>
                <tr>
                    <th>Component</th>
                    <th class="ta-right">Qty Expected</th>
                    <th class="ta-right">Qty Received</th>
                    <th class="ta-right">Qty Remaining</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asn->lines as $l)
                    <tr>
                        <td>
                            <span class="mono">{{ $l->component }}</span>
                            <span class="muted-sub">{{ $l->component_name }}</span>
                        </td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_expected) }} {{ $l->uom }}</td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_received) }} {{ $l->uom }}</td>
                        <td class="ta-right mono {{ $l->qty_remaining > 0 ? 'qty-out' : 'qty-in' }}">{{ fmt_qty($l->qty_remaining) }} {{ $l->uom }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Related Goods Receive Notes</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>GRN No</th>
                    <th>Received Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asn->grns as $g)
                    <tr>
                        <td class="mono">{{ $g->grn_no }}</td>
                        <td class="mono">{{ optional($g->received_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $g->status }}">{{ ucfirst($g->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('inbound.grns.show', $g) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No GRN created from this ASN yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
