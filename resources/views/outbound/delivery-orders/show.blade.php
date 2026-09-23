@extends('layouts.app')
@section('title', $deliveryOrder->do_no)

@section('content')
    <a href="{{ route('outbound.delivery-orders.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title mono">{{ $deliveryOrder->do_no }}</div>
                <div class="report-panel-caption">Delivery Order</div>
            </div>
            <span class="status-tag status-{{ $deliveryOrder->status }}">{{ ucfirst($deliveryOrder->status) }}</span>
        </div>

        <div class="detail-grid">
            <div class="detail-field">
                <label>Sales Order</label>
                <div class="val">
                    @if ($deliveryOrder->salesOrder)
                        <a href="{{ route('outbound.sales-orders.show', $deliveryOrder->salesOrder) }}" class="mono">{{ $deliveryOrder->salesOrder->so_no }}</a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="detail-field">
                <label>Customer</label>
                <div class="val">{{ $deliveryOrder->salesOrder->customer->name ?? '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Source Picking</label>
                <div class="val">
                    @if ($deliveryOrder->picking)
                        <a href="{{ route('outbound.pickings.show', $deliveryOrder->picking) }}" class="mono">{{ $deliveryOrder->picking->picking_no }}</a>
                    @else
                        — (manual entry)
                    @endif
                </div>
            </div>
            <div class="detail-field">
                <label>Delivery Date</label>
                <div class="val">{{ optional($deliveryOrder->delivery_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Vehicle No</label>
                <div class="val mono">{{ $deliveryOrder->vehicle_no ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Driver</label>
                <div class="val">{{ $deliveryOrder->driver_name ?: '—' }}</div>
            </div>
            <div class="detail-field" style="grid-column:1/-1;">
                <label>Remark</label>
                <div class="val">{{ $deliveryOrder->remark ?: '—' }}</div>
            </div>
        </div>

        <div class="detail-actions">
            <a href="{{ route('outbound.delivery-orders.pdf', $deliveryOrder) }}" class="btn-secondary">Download PDF</a>
        </div>
    </div>

    <div class="section-title">Line Items</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Part Code</th>
                    <th class="ta-right">Qty Delivered</th>
                    <th>Lot No</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deliveryOrder->lines as $l)
                    <tr>
                        <td>
                            <span class="mono">{{ $l->component }}</span>
                            <span class="muted-sub">{{ $l->component_name }}</span>
                        </td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_delivered) }} {{ $l->uom }}</td>
                        <td class="mono">{{ $l->lot_no ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="table-empty-msg">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
