@extends('layouts.app')
@section('title', 'Advance Shipment Notice')

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
            <a href="{{ route('inbound.asns.create') }}" class="btn-primary">+ New ASN</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ASN No</th>
                    <th>Supplier</th>
                    <th>Expected Date</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($asns as $a)
                    <tr>
                        <td class="mono">{{ $a->asn_no }}</td>
                        <td>{{ $a->supplier->name ?? '—' }}</td>
                        <td class="mono">{{ optional($a->expected_date)->format('d M Y') ?: '—' }}</td>
                        <td><span class="status-tag status-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                        <td class="mono">{{ $a->created_at->format('d M Y H:i') }}</td>
                        <td class="ta-right"><a href="{{ route('inbound.asns.show', $a) }}" class="btn-secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No ASN yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($asns->total() > 0)
                Showing {{ $asns->firstItem() }}&ndash;{{ $asns->lastItem() }} of {{ number_format($asns->total()) }} ASN
            @else
                No ASN
            @endif
        </span>
        <span class="pager-nav">
            @if ($asns->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $asns->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $asns->currentPage() }} / {{ max($asns->lastPage(), 1) }}</span>
            @if ($asns->hasMorePages())
                <a href="{{ $asns->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
