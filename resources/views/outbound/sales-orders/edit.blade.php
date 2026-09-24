@extends('layouts.app')
@section('title', 'Edit Sales Order')

@section('content')
    <a href="{{ route('outbound.sales-orders.show', $salesOrder) }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">Edit Sales Order</div>
                <div class="report-panel-caption mono">{{ $salesOrder->so_no }}</div>
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

        @if ($sellableItems->isEmpty())
            <div class="note-danger">No components currently in stock. Receive and put away stock (Inbound) before editing this Sales Order.</div>
        @endif

        <form method="POST" action="{{ route('outbound.sales-orders.update', $salesOrder) }}" id="so-form">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field">
                    <label for="so_no">SO No <span class="req">*</span></label>
                    <input type="text" name="so_no" id="so_no" value="{{ old('so_no', $salesOrder->so_no) }}" class="form-input mono" required>
                </div>
                <div class="form-field">
                    <label for="customer_id">Customer <span class="req">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select" required>
                        <option value="">Select customer</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id', $salesOrder->customer_id) == $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="order_date">Order Date <span class="req">*</span></label>
                    <input type="date" name="order_date" id="order_date" value="{{ old('order_date', optional($salesOrder->order_date)->format('Y-m-d')) }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="required_date">Required Date</label>
                    <input type="date" name="required_date" id="required_date" value="{{ old('required_date', optional($salesOrder->required_date)->format('Y-m-d')) }}" class="form-input">
                </div>
                <div class="form-field span-2">
                    <label for="remark">Remark</label>
                    <input type="text" name="remark" id="remark" value="{{ old('remark', $salesOrder->remark) }}" class="form-input">
                </div>
            </div>

            <div class="section-gap"></div>
            <div class="section-title">Line Items</div>

            <div class="table-wrap">
                <table class="line-items-table">
                    <thead>
                        <tr>
                            <th style="width:16%">Part Code</th>
                            <th>Part Name</th>
                            <th style="width:16%">Qty Ordered</th>
                            <th style="width:12%">UOM</th>
                            <th>Remark</th>
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
                <a href="{{ route('outbound.sales-orders.show', $salesOrder) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <template id="line-row-template">
        <tr>
            <td>
                <select name="lines[__i__][component]" class="mono line-component" required>
                    <option value="">Select code</option>
                    @foreach ($sellableItems as $item)
                        <option value="{{ $item->component }}" data-name="{{ $item->component_name }}" data-uom="{{ $item->unit }}">{{ $item->component }} ({{ fmt_qty($item->qty_total) }} {{ $item->unit }} in stock)</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" class="line-component-name" readonly tabindex="-1"></td>
            <td><input type="number" name="lines[__i__][qty_ordered]" step="0.01" min="0.01" required></td>
            <td><input type="text" name="lines[__i__][uom]" class="line-uom" value="Pcs" required></td>
            <td><input type="text" name="lines[__i__][remark]"></td>
            <td class="ta-right"><button type="button" class="btn-danger-ghost remove-line">Remove</button></td>
        </tr>
    </template>
@endsection

@php
    $existingSoLines = $salesOrder->lines->map(fn ($l) => [
        'component' => $l->component,
        'qty_ordered' => (float) $l->qty_ordered,
        'uom' => $l->uom,
        'remark' => $l->remark,
    ]);
@endphp

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('lines-body');
        const tpl = document.getElementById('line-row-template').innerHTML;
        const existingLines = @json($existingSoLines);
        let i = 0;

        function addRow(prefill) {
            const html = tpl.replaceAll('__i__', i++);
            body.insertAdjacentHTML('beforeend', html);
            const row = body.lastElementChild;
            if (prefill) {
                const select = row.querySelector('.line-component');
                select.value = prefill.component;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                row.querySelector('input[name$="[qty_ordered]"]').value = prefill.qty_ordered;
                row.querySelector('.line-uom').value = prefill.uom;
                row.querySelector('input[name$="[remark]"]').value = prefill.remark ?? '';
            }
        }

        document.getElementById('add-line').addEventListener('click', () => addRow());

        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-line')) {
                e.target.closest('tr').remove();
            }
        });

        // Part Name & UOM mengikuti kode yang dipilih (data dari stok riil).
        body.addEventListener('change', function (e) {
            if (!e.target.classList.contains('line-component')) return;
            const opt = e.target.selectedOptions[0];
            const row = e.target.closest('tr');
            row.querySelector('.line-component-name').value = opt?.dataset.name ?? '';
            if (opt?.dataset.uom) {
                row.querySelector('.line-uom').value = opt.dataset.uom;
            }
        });

        document.getElementById('so-form').addEventListener('submit', function (e) {
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
