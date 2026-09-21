@extends('layouts.app')
@section('title', 'Suppliers')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif

    <div class="report-header">
        <form method="GET" class="filters" style="margin-bottom:0;">
            <div class="filter-field">
                <label for="f-q">Search</label>
                <input type="text" name="q" id="f-q" value="{{ $search }}" placeholder="Code or name" class="filter-date">
            </div>
            <button class="filter-btn filter-field-btn">Search</button>
        </form>
        <div class="report-actions">
            <a href="{{ route('inbound.suppliers.create') }}" class="btn-primary-in">+ Add Supplier</a>
        </div>
    </div>

    <div class="table-wrap table-wrap-in">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Contact Person</th>
                    <th>Phone / Email</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $s)
                    <tr>
                        <td class="mono">{{ $s->code }}</td>
                        <td>{{ $s->name }}
                            @if ($s->address)
                                <span class="muted-sub">{{ $s->address }}</span>
                            @endif
                        </td>
                        <td>{{ $s->contact_person ?: '—' }}</td>
                        <td>
                            <span class="mono">{{ $s->phone ?: '—' }}</span>
                            @if ($s->email)
                                <span class="muted-sub">{{ $s->email }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($s->is_active)
                                <span class="status-tag status-completed">Active</span>
                            @else
                                <span class="status-tag status-draft">Inactive</span>
                            @endif
                        </td>
                        <td class="ta-right"><a href="{{ route('inbound.suppliers.edit', $s) }}" class="btn-secondary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No suppliers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($suppliers->total() > 0)
                Showing {{ $suppliers->firstItem() }}&ndash;{{ $suppliers->lastItem() }} of {{ number_format($suppliers->total()) }} suppliers
            @else
                No suppliers
            @endif
        </span>
        <span class="pager-nav">
            @if ($suppliers->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $suppliers->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $suppliers->currentPage() }} / {{ max($suppliers->lastPage(), 1) }}</span>
            @if ($suppliers->hasMorePages())
                <a href="{{ $suppliers->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
