@extends('layouts.app')
@section('title', 'New GRN')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">New Goods Receive Note</div>
            </div>
        </div>

        @if (! $selectedAsn)
            <form method="GET" class="filters">
                <div class="filter-field span-2">
                    <label for="asn_id">Prefill from ASN (optional)</label>
                    <select name="asn_id" id="asn_id" class="filter-select">
                        <option value="">— Manual entry, no ASN —</option>
                        @foreach ($openAsns as $a)
                            <option value="{{ $a->id }}">{{ $a->asn_no }} — {{ $a->supplier->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="filter-btn filter-field-btn">Load</button>
            </form>
        @else
            <div class="note-info">Prefilled from ASN <strong class="mono">{{ $selectedAsn->asn_no }}</strong> — <a href="{{ route('inbound.grns.create') }}">switch to manual entry</a></div>
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

        <form method="POST" action="{{ route('inbound.grns.store') }}" id="grn-form">
            @csrf
            @if ($selectedAsn)
                <input type="hidden" name="asn_id" value="{{ $selectedAsn->id }}">
            @endif
            <div class="form-grid">
                <div class="form-field">
                    <label for="grn_no">GRN No <span class="req">*</span></label>
                    <input type="text" name="grn_no" id="grn_no" value="{{ old('grn_no', $nextNo) }}" class="form-input mono" required>
                </div>
                <div class="form-field">
                    <label for="supplier_id">Supplier <span class="req">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', $selectedAsn->supplier_id ?? null) == $s->id)>{{ $s->code }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="received_date">Received Date <span class="req">*</span></label>
                    <input type="date" name="received_date" id="received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="transport_mode">Transport Mode</label>
                    <input type="text" name="transport_mode" id="transport_mode" value="{{ old('transport_mode') }}" placeholder="e.g. Truck" class="form-input">
                </div>
                <div class="form-field">
                    <label for="vehicle_no">Vehicle No</label>
                    <input type="text" name="vehicle_no" id="vehicle_no" value="{{ old('vehicle_no') }}" class="form-input mono">
                </div>
                <div class="form-field span-3">
                    <label for="remark">Remark</label>
                    <input type="text" name="remark" id="remark" value="{{ old('remark') }}" class="form-input">
                </div>
            </div>

            <div class="section-gap"></div>
            <div class="section-title">Line Items</div>

            <div class="table-wrap">
                <table class="line-items-table">
                    <thead>
                        <tr>
                            <th style="width:12%">Component</th>
                            <th>Component Name</th>
                            <th style="width:10%">Qty Received</th>
                            <th style="width:8%">UOM</th>
                            <th style="width:12%">Lot No</th>
                            <th style="width:14%">Mfg Date</th>
                            <th style="width:14%">Expired Date</th>
                            <th style="width:6%"></th>
                        </tr>
                    </thead>
                    <tbody id="lines-body"></tbody>
                </table>
            </div>

            <div class="line-items-foot">
                <button type="button" class="add-line-btn" id="add-line">+ Add Line</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save GRN</button>
                <a href="{{ route('inbound.grns.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <template id="line-row-template">
        <tr>
            <td><input type="text" name="lines[__i__][component]" class="mono line-component" list="known-components" required></td>
            <td><input type="text" name="lines[__i__][component_name]" class="line-component_name"></td>
            <td><input type="number" name="lines[__i__][qty_received]" step="0.01" min="0.01" class="line-qty_received" required></td>
            <td><input type="text" name="lines[__i__][uom]" class="line-uom" value="Pcs" required></td>
            <td><input type="text" name="lines[__i__][lot_no]"></td>
            <td><input type="date" name="lines[__i__][mfg_date]"></td>
            <td><input type="date" name="lines[__i__][expired_date]"></td>
            <td class="ta-right"><button type="button" class="btn-danger-ghost remove-line">Remove</button></td>
        </tr>
    </template>

    <datalist id="known-components">
        @foreach ($knownComponents as $c)
            <option value="{{ $c->component }}">{{ $c->component_name }}</option>
        @endforeach
    </datalist>
@endsection

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('lines-body');
        const tpl = document.getElementById('line-row-template').innerHTML;
        const prefill = @json($prefillLines);
        const known = @json($knownComponents->keyBy('component')->map(fn ($c) => ['name' => $c->component_name, 'uom' => $c->unit]));
        let i = 0;

        function addRow(data) {
            const html = tpl.replaceAll('__i__', i++);
            body.insertAdjacentHTML('beforeend', html);
            if (data) {
                const row = body.lastElementChild;
                row.querySelector('.line-component').value = data.component ?? '';
                row.querySelector('.line-component_name').value = data.component_name ?? '';
                row.querySelector('.line-qty_received').value = data.qty ?? '';
                row.querySelector('.line-uom').value = data.uom ?? 'Pcs';
            }
        }

        document.getElementById('add-line').addEventListener('click', function () { addRow(); });

        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-line')) {
                e.target.closest('tr').remove();
            }
        });

        body.addEventListener('input', function (e) {
            if (!e.target.classList.contains('line-component')) return;
            const match = known[e.target.value];
            if (!match) return;
            const row = e.target.closest('tr');
            row.querySelector('.line-component_name').value = match.name ?? '';
            row.querySelector('.line-uom').value = match.uom ?? 'Pcs';
        });

        document.getElementById('grn-form').addEventListener('submit', function (e) {
            if (!body.querySelector('tr')) {
                e.preventDefault();
                alert('Add at least one line item.');
            }
        });

        if (prefill.length) {
            prefill.forEach(addRow);
        } else {
            addRow();
        }
    })();
</script>
@endpush
