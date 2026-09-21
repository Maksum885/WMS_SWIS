@extends('layouts.app')
@section('title', 'Delivery Order')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <div class="report-header">
        <div></div>
        <div class="report-actions">
            <a href="{{ route('outbound.delivery-orders.create') }}" class="btn-primary">+ New Delivery Order</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>DO No</th>
                    <th>SO No</th>
                    <th>Customer</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deliveryOrders as $d)
                    <tr>
                        <td class="mono">{{ $d->do_no }}</td>
                        <td class="mono">{{ $d->salesOrder->so_no ?? '—' }}</td>
                        <td>{{ $d->salesOrder->customer->name ?? '—' }}</td>
                        <td class="mono">{{ optional($d->delivery_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('outbound.delivery-orders.show', $d) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No Delivery Order recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($deliveryOrders->total() > 0)
                Showing {{ $deliveryOrders->firstItem() }}&ndash;{{ $deliveryOrders->lastItem() }} of {{ number_format($deliveryOrders->total()) }} Delivery Order
            @else
                No Delivery Order
            @endif
        </span>
        <span class="pager-nav">
            @if ($deliveryOrders->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $deliveryOrders->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $deliveryOrders->currentPage() }} / {{ max($deliveryOrders->lastPage(), 1) }}</span>
            @if ($deliveryOrders->hasMorePages())
                <a href="{{ $deliveryOrders->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
