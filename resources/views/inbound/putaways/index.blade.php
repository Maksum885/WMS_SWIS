@extends('layouts.app')
@section('title', 'Put Away')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <div class="report-header">
        <div></div>
        <div class="report-actions">
            <a href="{{ route('inbound.putaways.create') }}" class="btn-primary">+ New Put Away</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Put Away No</th>
                    <th>GRN No</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($putaways as $p)
                    <tr>
                        <td class="mono">{{ $p->putaway_no }}</td>
                        <td class="mono">{{ $p->grn->grn_no ?? '—' }}</td>
                        <td>{{ $p->grn->supplier->name ?? '—' }}</td>
                        <td class="mono">{{ optional($p->putaway_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
                        <td class="ta-right"><a href="{{ route('inbound.putaways.show', $p) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No Put Away recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($putaways->total() > 0)
                Showing {{ $putaways->firstItem() }}&ndash;{{ $putaways->lastItem() }} of {{ number_format($putaways->total()) }} Put Away
            @else
                No Put Away
            @endif
        </span>
        <span class="pager-nav">
            @if ($putaways->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $putaways->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $putaways->currentPage() }} / {{ max($putaways->lastPage(), 1) }}</span>
            @if ($putaways->hasMorePages())
                <a href="{{ $putaways->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
