@extends('layouts.app')
@section('title', $salesOrder->so_no)

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="note-danger">{{ $errors->first() }}</div>
    @endif

    <a href="{{ route('outbound.sales-orders.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title mono">{{ $salesOrder->so_no }}</div>
                <div class="report-panel-caption">Sales Order</div>
            </div>
            <span class="status-tag status-{{ $salesOrder->status }}">{{ ucfirst($salesOrder->status) }}</span>
        </div>

        <div class="detail-grid">
            <div class="detail-field">
                <label>Customer</label>
                <div class="val">{{ $salesOrder->customer->name ?? '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Order Date</label>
                <div class="val">{{ optional($salesOrder->order_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Required Date</label>
                <div class="val">{{ optional($salesOrder->required_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field" style="grid-column:1/-1;">
                <label>Remark</label>
                <div class="val">{{ $salesOrder->remark ?: '—' }}</div>
            </div>
        </div>

        <div class="detail-actions">
            @if ($salesOrder->status === 'draft')
                <form method="POST" action="{{ route('outbound.sales-orders.confirm', $salesOrder) }}" onsubmit="return confirm('Confirm this Sales Order? Once confirmed it can be used to create a Picking.');">
                    @csrf
                    <button type="submit" class="btn-primary">Confirm Sales Order</button>
                </form>
            @else
                <a href="{{ route('outbound.pickings.create', ['sales_order_id' => $salesOrder->id]) }}" class="btn-primary">Create Picking from this SO</a>
            @endif
            <a href="{{ route('outbound.sales-orders.pdf', $salesOrder) }}" class="btn-secondary">Download PDF</a>
        </div>
    </div>

    <div class="section-title">Line Items</div>
    <div class="table-wrap section-gap">
        <table>
            <thead>
                <tr>
                    <th>Part Code</th>
                    <th class="ta-right">Qty Ordered</th>
                    <th class="ta-right">Qty Picked</th>
                    <th class="ta-right">Qty Remaining</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salesOrder->lines as $l)
                    @php $remaining = max(0, (float) $l->qty_ordered - $l->qty_picked); @endphp
                    <tr>
                        <td>
                            <span class="mono">{{ $l->matrixPartcode->partcode ?? '—' }}</span>
                            <span class="muted-sub">{{ $l->matrixPartcode->model_name ?? '' }}</span>
                        </td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_ordered) }} {{ $l->uom }}</td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty_picked) }} {{ $l->uom }}</td>
                        <td class="ta-right mono {{ $remaining > 0 ? 'qty-out' : 'qty-in' }}">{{ fmt_qty($remaining) }} {{ $l->uom }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No line items.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Related Pickings</div>
    <div class="table-wrap section-gap">
        <table>
            <thead>
                <tr>
                    <th>Picking No</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salesOrder->pickings as $p)
                    <tr>
                        <td class="mono">{{ $p->picking_no }}</td>
                        <td class="mono">{{ optional($p->picking_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('outbound.pickings.show', $p) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No Picking created from this SO yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section-title">Related Delivery Orders</div>
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
                @forelse ($salesOrder->deliveryOrders as $d)
                    <tr>
                        <td class="mono">{{ $d->do_no }}</td>
                        <td class="mono">{{ optional($d->delivery_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('outbound.delivery-orders.show', $d) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="table-empty-msg">No Delivery Order created from this SO yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
