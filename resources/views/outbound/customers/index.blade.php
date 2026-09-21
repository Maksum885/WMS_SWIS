@extends('layouts.app')
@section('title', 'Customers')

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
            <a href="{{ route('outbound.customers.create') }}" class="btn-primary">+ Add Customer</a>
        </div>
    </div>

    <div class="table-wrap">
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
                @forelse ($customers as $c)
                    <tr>
                        <td class="mono">{{ $c->code }}</td>
                        <td>{{ $c->name }}
                            @if ($c->address)
                                <span class="muted-sub">{{ $c->address }}</span>
                            @endif
                        </td>
                        <td>{{ $c->contact_person ?: '—' }}</td>
                        <td>
                            <span class="mono">{{ $c->phone ?: '—' }}</span>
                            @if ($c->email)
                                <span class="muted-sub">{{ $c->email }}</span>
                            @endif
                        </td>
                        <td>
                            @if ($c->is_active)
                                <span class="status-tag status-completed">Active</span>
                            @else
                                <span class="status-tag status-draft">Inactive</span>
                            @endif
                        </td>
                        <td class="ta-right"><a href="{{ route('outbound.customers.edit', $c) }}" class="btn-secondary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty-msg">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pager">
        <span class="pager-info">
            @if ($customers->total() > 0)
                Showing {{ $customers->firstItem() }}&ndash;{{ $customers->lastItem() }} of {{ number_format($customers->total()) }} customers
            @else
                No customers
            @endif
        </span>
        <span class="pager-nav">
            @if ($customers->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $customers->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $customers->currentPage() }} / {{ max($customers->lastPage(), 1) }}</span>
            @if ($customers->hasMorePages())
                <a href="{{ $customers->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
