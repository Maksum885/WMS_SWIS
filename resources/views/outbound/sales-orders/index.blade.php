@extends('layouts.app')
@section('title', 'Sales Order')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <div class="report-header">
        <form method="GET" class="filters" style="margin-bottom:0;">
            <div class="filter-field">
                <label for="f-status">Status</label>
                <select name="status" id="f-status" class="filter-select">
                    <option value="">All statuses</option>
                    <option value="draft" @selected($status === 'draft')>Draft</option>
                    <option value="confirmed" @selected($status === 'confirmed')>Confirmed</option>
                </select>
            </div>
            <button class="filter-btn filter-field-btn">Filter</button>
        </form>
        <div class="report-actions">
            <a href="{{ route('outbound.sales-orders.create') }}" class="btn-primary">+ New Sales Order</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>SO No</th>
                    <th>Customer</th>
                    <th>Order Date</th>
                    <th>Required Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salesOrders as $so)
                    <tr>
                        <td class="mono">{{ $so->so_no }}</td>
                        <td>{{ $so->customer->name ?? '—' }}</td>
                        <td class="mono">{{ optional($so->order_date)->format('d M Y') ?: '—' }}</td>
                        <td class="mono">{{ optional($so->required_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $so->status }}">{{ ucfirst($so->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('outbound.sales-orders.show', $so) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No Sales Order yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($salesOrders->total() > 0)
                Showing {{ $salesOrders->firstItem() }}&ndash;{{ $salesOrders->lastItem() }} of {{ number_format($salesOrders->total()) }} Sales Order
            @else
                No Sales Order
            @endif
        </span>
        <span class="pager-nav">
            @if ($salesOrders->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $salesOrders->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $salesOrders->currentPage() }} / {{ max($salesOrders->lastPage(), 1) }}</span>
            @if ($salesOrders->hasMorePages())
                <a href="{{ $salesOrders->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
