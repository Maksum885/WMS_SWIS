@extends('layouts.app')
@section('title', 'New ASN')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">New Advance Shipment Notice</div>
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

        <form method="POST" action="{{ route('inbound.asns.store') }}" id="asn-form">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label for="asn_no">ASN No <span class="req">*</span></label>
                    <input type="text" name="asn_no" id="asn_no" value="{{ old('asn_no', $nextNo) }}" class="form-input mono" required>
                </div>
                <div class="form-field">
                    <label for="supplier_id">Supplier <span class="req">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">Select supplier</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->code }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="expected_date">Expected Date</label>
                    <input type="date" name="expected_date" id="expected_date" value="{{ old('expected_date') }}" class="form-input">
                </div>
                <div class="form-field">
                    <label for="po_number">PO Number</label>
                    <input type="text" name="po_number" id="po_number" value="{{ old('po_number') }}" class="form-input mono" placeholder="Purchase order reference">
                </div>
                <div class="form-field span-2">
                    <label for="remark">Remark</label>
                    <input type="text" name="remark" id="remark" value="{{ old('remark') }}" class="form-input">
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
                <button type="submit" class="btn-primary">Save ASN</button>
                <a href="{{ route('inbound.asns.index') }}" class="btn-secondary">Cancel</a>
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

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('lines-body');
        const tpl = document.getElementById('line-row-template').innerHTML;
        let i = 0;

        function addRow() {
            const html = tpl.replaceAll('__i__', i++);
            body.insertAdjacentHTML('beforeend', html);
        }

        document.getElementById('add-line').addEventListener('click', addRow);

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
            if (opt?.dataset.uom) {
                row.querySelector('.line-uom').value = opt.dataset.uom;
            }
        });

        document.getElementById('asn-form').addEventListener('submit', function (e) {
            if (!body.querySelector('tr')) {
                e.preventDefault();
                alert('Add at least one line item.');
            }
        });

        addRow();
    })();
</script>
@endpush
