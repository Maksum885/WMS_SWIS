@extends('layouts.app')
@section('title', $picking->picking_no)

@section('content')
    @php
        $hasDelivered = $picking->deliveryOrders->isNotEmpty();
    @endphp

    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <a href="{{ route('outbound.pickings.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title mono">{{ $picking->picking_no }}</div>
                <div class="report-panel-caption">Picking Confirmation</div>
            </div>
            <span class="status-tag status-{{ $picking->status }}">{{ ucfirst($picking->status) }}</span>
        </div>

        <div class="detail-grid">
            <div class="detail-field">
                <label>Source SO</label>
                <div class="val">
                    @if ($picking->salesOrder)
                        <a href="{{ route('outbound.sales-orders.show', $picking->salesOrder) }}" class="mono">{{ $picking->salesOrder->so_no }}</a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="detail-field">
                <label>Customer</label>
                <div class="val">{{ $picking->salesOrder->customer->name ?? '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Date</label>
                <div class="val">{{ optional($picking->picking_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Staff</label>
                <div class="val">{{ $picking->created_by ?: '—' }}</div>
            </div>
        </div>

        <div class="detail-actions">
            @if (! $hasDelivered && $picking->status === 'completed')
                <a href="{{ route('outbound.delivery-orders.create', ['picking_id' => $picking->id]) }}" class="btn-primary">Create Delivery Order from this Picking</a>
            @endif
            <a href="{{ route('outbound.pickings.pdf', $picking) }}" class="btn-secondary">Download PDF</a>
        </div>
    </div>

    <div class="section-title">Lines</div>
    <div class="table-wrap section-gap">
        <table>
            <thead>
                <tr>
                    <th>Part Code</th>
                    <th class="ta-right">Qty Picked</th>
                    <th>Lot No</th>
                    <th>Location</th>
                    <th>Confirmed At</th>
                    <th>Confirmed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($picking->lines as $l)
                    <tr>
                        <td>
                            <span class="mono">{{ $l->matrixPartcode->partcode ?? '—' }}</span>
                            <span class="muted-sub">{{ $l->matrixPartcode->model_name ?? '' }}</span>
                        </td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_picked) }} {{ $l->soLine->uom ?? '' }}</td>
                        <td class="mono">{{ $l->lot_no ?: '—' }}</td>
                        <td class="mono">{{ $l->location_code ?: '—' }}</td>
                        <td class="mono">{{ optional($l->confirmed_at)->format('d M Y H:i') ?: '—' }}</td>
                        <td>{{ $l->confirmed_by ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Delivery Orders</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>DO No</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($picking->deliveryOrders as $d)
                    <tr>
                        <td class="mono">{{ $d->do_no }}</td>
                        <td class="mono">{{ optional($d->delivery_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('outbound.delivery-orders.show', $d) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No Delivery Order created from this Picking yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
