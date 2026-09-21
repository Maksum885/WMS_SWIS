@extends('layouts.app')
@section('title', 'Component Master')

@section('content')
    @if (session('status'))
        <div class="note-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="note-danger">
            <ul style="margin:0;padding-left:18px;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="report-header">
        <form method="GET" class="filters" style="margin-bottom:0;">
            <div class="filter-field">
                <label for="f-q">Search</label>
                <input type="text" name="q" id="f-q" value="{{ $search }}" placeholder="Code or name" class="filter-date mono">
            </div>
            <button class="filter-btn filter-field-btn">Search</button>
        </form>
        <div class="report-actions">
            <a href="{{ route('master.components.create') }}" class="btn-primary-in">+ Add Component</a>
        </div>
    </div>

    <form method="POST" action="{{ route('master.components.print') }}">
        @csrf
        <div class="table-wrap table-wrap-in">
            <table>
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>Component Code</th>
                        <th>Component Name</th>
                        <th>UOM</th>
                        <th class="ta-right">Qty on Label</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($components as $c)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $c->id }}"></td>
                            <td class="mono">{{ $c->component_code }}</td>
                            <td>{{ $c->component_name }}</td>
                            <td class="mono">{{ $c->default_uom }}</td>
                            <td class="ta-right mono">{{ fmt_qty($c->qty_label) }}</td>
                            <td class="ta-right"><a href="{{ route('master.components.edit', $c) }}" class="btn-secondary">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty-msg">No components registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($components->count() > 0)
            <div class="form-actions" style="margin-top:14px;">
                <button type="submit" class="btn-export">Download PDF (selected)</button>
            </div>
        @endif
    </form>

    <div class="pager">
        <span class="pager-info">
            @if ($components->total() > 0)
                Showing {{ $components->firstItem() }}&ndash;{{ $components->lastItem() }} of {{ number_format($components->total()) }} components
            @else
                No components
            @endif
        </span>
        <span class="pager-nav">
            @if ($components->onFirstPage())
                <span class="pager-btn disabled">&larr; Previous</span>
            @else
                <a href="{{ $components->previousPageUrl() }}" class="pager-btn">&larr; Previous</a>
            @endif
            <span class="pager-page">Page {{ $components->currentPage() }} / {{ max($components->lastPage(), 1) }}</span>
            @if ($components->hasMorePages())
                <a href="{{ $components->nextPageUrl() }}" class="pager-btn">Next &rarr;</a>
            @else
                <span class="pager-btn disabled">Next &rarr;</span>
            @endif
        </span>
    </div>
@endsection
