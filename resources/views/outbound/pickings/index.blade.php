@extends('layouts.app')
@section('title', 'Picking')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <div class="report-header">
        <div></div>
        <div class="report-actions">
            <a href="{{ route('outbound.pickings.create') }}" class="btn-primary">+ New Picking</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Picking No</th>
                    <th>SO No</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pickings as $p)
                    <tr>
                        <td class="mono">{{ $p->picking_no }}</td>
                        <td class="mono">{{ $p->salesOrder->so_no ?? '—' }}</td>
                        <td>{{ $p->salesOrder->customer->name ?? '—' }}</td>
                        <td class="mono">{{ optional($p->picking_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('outbound.pickings.show', $p) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No Picking recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($pickings->total() > 0)
                Showing {{ $pickings->firstItem() }}&ndash;{{ $pickings->lastItem() }} of {{ number_format($pickings->total()) }} Picking
            @else
                No Picking
            @endif
        </span>
        <span class="pager-nav">
            @if ($pickings->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $pickings->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $pickings->currentPage() }} / {{ max($pickings->lastPage(), 1) }}</span>
            @if ($pickings->hasMorePages())
                <a href="{{ $pickings->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
