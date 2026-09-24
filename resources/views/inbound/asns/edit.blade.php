@extends('layouts.app')
@section('title', 'Edit ASN')

@section('content')
    <a href="{{ route('inbound.asns.show', $asn) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">Edit Advance Shipment Notice</div>
                <div class="report-panel-caption mono">{{ $asn->asn_no }}</div>
            </div>
        </div>

        @if ($errors->any())
            <div class="note-danger">
                <ul style="margin:0;padding-left:18px;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('inbound.asns.update', $asn) }}" id="asn-form">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field">
                    <label for="asn_no">ASN No <span class="req">*</span></label>
                    <input type="text" name="asn_no" id="asn_no" value="{{ old('asn_no', $asn->asn_no) }}" class="form-input mono" required>
                </div>
                <div class="form-field">
                    <label for="supplier_id">Supplier <span class="req">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', $asn->supplier_id) == $s->id)>{{ $s->code }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="expected_date">Expected Date</label>
                    <input type="date" name="expected_date" id="expected_date" value="{{ old('expected_date', optional($asn->expected_date)->format('Y-m-d')) }}" class="form-input">
                </div>
                <div class="form-field">
                    <label for="po_number">PO Number</label>
                    <input type="text" name="po_number" id="po_number" value="{{ old('po_number', $asn->po_number) }}" class="form-input mono" placeholder="Purchase order reference">
                </div>
                <div class="form-field span-2">
                    <label for="remark">Remark</label>
                    <input type="text" name="remark" id="remark" value="{{ old('remark', $asn->remark) }}" class="form-input">
                </div>
            </div>

            <div class="section-gap"></div>
            <div class="section-title">Line Items</div>

            @if ($components->isEmpty())
                <div class="note-danger">No components registered in <a href="{{ route('master.components.index') }}">Component Master</a> yet. Add one there before creating an ASN.</div>
            @endif

            <div class="table-wrap">
                <table class="line-items-table">
                    <thead>
                        <tr>
                            <th style="width:20%">Component Code</th>
                            <th>Component Name</th>
                            <th style="width:16%">Qty Expected</th>
                            <th style="width:12%">UOM</th>
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
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('inbound.asns.show', $asn) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <template id="line-row-template">
        <tr>
            <td>
                <select name="lines[__i__][component]" class="mono line-component" required>
                    <option value="">Select code</option>
                    @foreach ($components as $c)
                        <option value="{{ $c->component_code }}" data-name="{{ $c->component_name }}" data-uom="{{ $c->default_uom }}">{{ $c->component_code }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" class="line-component-name" readonly tabindex="-1"></td>
            <td><input type="number" name="lines[__i__][qty_expected]" step="0.01" min="0.01" required></td>
            <td><input type="text" name="lines[__i__][uom]" class="line-uom" value="Pcs" required></td>
            <td class="ta-right"><button type="button" class="btn-danger-ghost remove-line">Remove</button></td>
        </tr>
    </template>
@endsection

@php
    $existingAsnLines = $asn->lines->map(fn ($l) => [
        'component' => $l->component,
        'qty_expected' => (float) $l->qty_expected,
        'uom' => $l->uom,
    ]);
@endphp

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('lines-body');
        const tpl = document.getElementById('line-row-template').innerHTML;
        const existingLines = @json($existingAsnLines);
        let i = 0;

        function addRow(prefill) {
            const html = tpl.replaceAll('__i__', i++);
            body.insertAdjacentHTML('beforeend', html);
            const row = body.lastElementChild;
            if (prefill) {
                const select = row.querySelector('.line-component');
                select.value = prefill.component;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                row.querySelector('input[name$="[qty_expected]"]').value = prefill.qty_expected;
                row.querySelector('.line-uom').value = prefill.uom;
            }
        }

        document.getElementById('add-line').addEventListener('click', () => addRow());

        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-line')) {
                e.target.closest('tr').remove();
            }
        });

        // Component Name & UOM mengikuti kode yang dipilih (data dari Component Master).
        body.addEventListener('change', function (e) {
            if (!e.target.classList.contains('line-component')) return;
            const opt = e.target.selectedOptions[0];
            const row = e.target.closest('tr');
            row.querySelector('.line-component-name').value = opt?.dataset.name ?? '';
            if (opt?.dataset.uom && !row.dataset.uomLocked) {
                row.querySelector('.line-uom').value = opt.dataset.uom;
            }
        });

        document.getElementById('asn-form').addEventListener('submit', function (e) {
            if (!body.querySelector('tr')) {
                e.preventDefault();
                alert('Add at least one line item.');
            }
        });

        if (existingLines.length) {
            existingLines.forEach(addRow);
        } else {
            addRow();
        }
    })();
</script>
@endpush
