@extends('layouts.app')
@section('title', 'New Picking')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">New Picking</div>
            </div>
        </div>

        <form method="GET" class="filters">
            <div class="filter-field span-2">
                <label for="sales_order_id">Sales Order <span class="req">*</span></label>
                <select name="sales_order_id" id="sales_order_id" class="filter-select">
                    <option value="">Select confirmed SO with outstanding qty</option>
                    @foreach ($openSalesOrders as $so)
                        <option value="{{ $so->id }}" @selected($selectedSo && $selectedSo->id === $so->id)>{{ $so->so_no }} — {{ $so->customer->name ?? '' }}</option>
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

        @if (! $selectedSo)
            <div class="empty-state">Select a confirmed Sales Order above to see its outstanding parts and confirm where they were picked from.</div>
        @elseif (empty($prefillLines))
            <div class="empty-state">Sales Order <strong class="mono">{{ $selectedSo->so_no }}</strong> has no outstanding quantity left — everything has already been picked.</div>
        @else
            <form method="POST" action="{{ route('outbound.pickings.store') }}" id="picking-form">
                @csrf
                <input type="hidden" name="sales_order_id" value="{{ $selectedSo->id }}">

                <div class="form-grid">
                    <div class="form-field">
                        <label for="picking_no">Picking No <span class="req">*</span></label>
                        <input type="text" name="picking_no" id="picking_no" value="{{ old('picking_no', $nextNo) }}" class="form-input mono" required>
                    </div>
                    <div class="form-field">
                        <label for="picking_date">Date <span class="req">*</span></label>
                        <input type="date" name="picking_date" id="picking_date" value="{{ old('picking_date', now()->format('Y-m-d')) }}" class="form-input" required>
                    </div>
                    <div class="form-field">
                        <label for="created_by">Staff Name</label>
                        <input type="text" name="created_by" id="created_by" value="{{ old('created_by') }}" class="form-input">
                    </div>
                </div>

                <div class="section-gap"></div>
                <div class="section-title">Confirm Location per Part</div>

                <div class="table-wrap">
                    <table class="line-items-table">
                        <thead>
                            <tr>
                                <th>Part Code</th>
                                <th style="width:14%" class="ta-right">Outstanding</th>
                                <th style="width:14%" class="ta-right">Qty to Pick</th>
                                <th style="width:14%">Lot No</th>
                                <th style="width:20%">Rack Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prefillLines as $i => $line)
                                <tr>
                                    <td>
                                        <input type="hidden" name="lines[{{ $i }}][so_line_id]" value="{{ $line['so_line_id'] }}">
                                        <span class="mono">{{ $line['partcode'] }}</span>
                                        <span class="muted-sub">{{ $line['model_name'] }}</span>
                                    </td>
                                    <td class="ta-right mono">{{ fmt_qty($line['qty_outstanding']) }}</td>
                                    <td>
                                        <input type="number" name="lines[{{ $i }}][qty_picked]" step="0.01" min="0.01" max="{{ $line['qty_outstanding'] }}" value="{{ $line['qty_outstanding'] }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="lines[{{ $i }}][lot_no]">
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
                    <button type="submit" class="btn-primary">Confirm Picking</button>
                    <a href="{{ route('outbound.pickings.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        @endif
    </div>

    @include('partials.rack-picker-modal', ['pickerAvailableLabel' => 'Filled & available to pick'])
@endsection

@push('scripts')
<script src="{{ asset('vendor/threejs/three.min.js') }}"></script>
@include('partials.rack-picker-script')
@endpush
