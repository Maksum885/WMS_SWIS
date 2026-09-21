@extends('layouts.app')
@section('title', 'New Put Away')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">New Put Away</div>
            </div>
        </div>

        <form method="GET" class="filters">
            <div class="filter-field span-2">
                <label for="grn_id">Goods Receive Note <span class="req">*</span></label>
                <select name="grn_id" id="grn_id" class="filter-select">
                    <option value="">Select GRN with outstanding qty</option>
                    @foreach ($openGrns as $g)
                        <option value="{{ $g->id }}" @selected($selectedGrn && $selectedGrn->id === $g->id)>{{ $g->grn_no }} — {{ $g->supplier->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <button class="filter-btn filter-field-btn">Load</button>
        </form>

        @if ($errors->any())
            <div class="note-danger">
                <ul style="margin:0;padding-left:18px;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $selectedGrn)
            <div class="empty-state">Select a GRN above to see its outstanding components and confirm where they were placed.</div>
        @elseif (empty($prefillLines))
            <div class="empty-state">GRN <strong class="mono">{{ $selectedGrn->grn_no }}</strong> has no outstanding quantity left — everything has already been put away.</div>
        @else
            <form method="POST" action="{{ route('inbound.putaways.store') }}" id="putaway-form">
                @csrf
                <input type="hidden" name="grn_id" value="{{ $selectedGrn->id }}">

                <div class="form-grid">
                    <div class="form-field">
                        <label for="putaway_no">Put Away No <span class="req">*</span></label>
                        <input type="text" name="putaway_no" id="putaway_no" value="{{ old('putaway_no', $nextNo) }}" class="form-input mono" required>
                    </div>
                    <div class="form-field">
                        <label for="putaway_date">Date <span class="req">*</span></label>
                        <input type="date" name="putaway_date" id="putaway_date" value="{{ old('putaway_date', now()->format('Y-m-d')) }}" class="form-input" required>
                    </div>
                    <div class="form-field">
                        <label for="created_by">Staff Name</label>
                        <input type="text" name="created_by" id="created_by" value="{{ old('created_by') }}" class="form-input">
                    </div>
                </div>

                <div class="section-gap"></div>
                <div class="section-title">Confirm Location per Component</div>

                <div class="table-wrap">
                    <table class="line-items-table">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th style="width:14%">Lot No</th>
                                <th style="width:14%" class="ta-right">Outstanding</th>
                                <th style="width:14%" class="ta-right">Qty to Put Away</th>
                                <th style="width:20%">Rack Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prefillLines as $i => $line)
                                <tr>
                                    <td>
                                        <input type="hidden" name="lines[{{ $i }}][grn_line_id]" value="{{ $line['grn_line_id'] }}">
                                        <span class="mono">{{ $line['component'] }}</span>
                                        <span class="muted-sub">{{ $line['component_name'] }}</span>
                                    </td>
                                    <td class="mono">{{ $line['lot_no'] ?: '—' }}</td>
                                    <td class="ta-right mono">{{ fmt_qty($line['qty_outstanding']) }}</td>
                                    <td>
                                        <input type="number" name="lines[{{ $i }}][qty]" step="0.01" min="0.01" max="{{ $line['qty_outstanding'] }}" value="{{ $line['qty_outstanding'] }}" required>
                                    </td>
                                    <td>
                                        <div class="location-picker-cell">
                                            <input type="text" name="lines[{{ $i }}][location_code]" list="location-codes" class="mono" placeholder="e.g. R1C11" required>
                                            <button type="button" class="pick-3d-btn" onclick="window.openRackPicker(this.previousElementSibling)" title="Pick on 3D rack">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><polyline points="3.29 7 12 12 20.71 7"></polyline><line x1="12" y1="22" x2="12" y2="12"></line></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <datalist id="location-codes">
                    @foreach ($locationCodes as $code)
                        <option value="{{ $code }}">
                    @endforeach
                </datalist>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Confirm Put Away</button>
                    <a href="{{ route('inbound.putaways.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        @endif
    </div>

    @include('partials.rack-picker-modal', ['pickerAvailableLabel' => 'Empty & available'])
@endsection

@push('scripts')
<script src="{{ asset('vendor/threejs/three.min.js') }}"></script>
@include('partials.rack-picker-script')
@endpush
