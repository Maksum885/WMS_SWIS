@extends('layouts.app')
@section('title', 'Goods Receive Note')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <div class="report-header">
        <div></div>
        <div class="report-actions">
            <a href="{{ route('inbound.grns.create') }}" class="btn-primary">+ New GRN</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>GRN No</th>
                    <th>ASN No</th>
                    <th>Supplier</th>
                    <th>Received Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grns as $g)
                    <tr>
                        <td class="mono">{{ $g->grn_no }}</td>
                        <td class="mono">{{ $g->asn->asn_no ?? '—' }}</td>
                        <td>{{ $g->supplier->name ?? '—' }}</td>
                        <td class="mono">{{ optional($g->received_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $g->status }}">{{ ucfirst($g->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('inbound.grns.show', $g) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No GRN yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($grns->total() > 0)
                Showing {{ $grns->firstItem() }}&ndash;{{ $grns->lastItem() }} of {{ number_format($grns->total()) }} GRN
            @else
                No GRN
            @endif
        </span>
        <span class="pager-nav">
            @if ($grns->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $grns->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $grns->currentPage() }} / {{ max($grns->lastPage(), 1) }}</span>
            @if ($grns->hasMorePages())
                <a href="{{ $grns->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
