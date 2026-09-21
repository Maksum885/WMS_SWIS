@extends('layouts.app')
@section('title', 'New Sales Order')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">New Sales Order</div>
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

        @if ($matrixPartcodes->isEmpty())
            <div class="note-danger">No finished-good part codes found in <span class="mono">matrix_partcode</span>. Add one there before creating a Sales Order.</div>
        @endif

        <form method="POST" action="{{ route('outbound.sales-orders.store') }}" id="so-form">
            @csrf
            <div class="form-grid">
                <div class="form-field">
                    <label for="so_no">SO No <span class="req">*</span></label>
                    <input type="text" name="so_no" id="so_no" value="{{ old('so_no', $nextNo) }}" class="form-input mono" required>
                </div>
                <div class="form-field">
                    <label for="customer_id">Customer <span class="req">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select" required>
                        <option value="">Select customer</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="order_date">Order Date <span class="req">*</span></label>
                    <input type="date" name="order_date" id="order_date" value="{{ old('order_date', now()->format('Y-m-d')) }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="required_date">Required Date</label>
                    <input type="date" name="required_date" id="required_date" value="{{ old('required_date') }}" class="form-input">
                </div>
                <div class="form-field span-2">
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
                            <th>Part Code</th>
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
                <button type="submit" class="btn-primary">Save Sales Order</button>
                <a href="{{ route('outbound.sales-orders.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <template id="line-row-template">
        <tr>
            <td>
                <select name="lines[__i__][matrix_partcode_id]" required>
                    <option value="">Select part</option>
                    @foreach ($matrixPartcodes as $mp)
                        <option value="{{ $mp->id }}">{{ $mp->partcode }} — {{ $mp->model_name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="lines[__i__][qty_ordered]" step="0.01" min="0.01" required></td>
            <td><input type="text" name="lines[__i__][uom]" value="Pcs" required></td>
            <td><input type="text" name="lines[__i__][remark]"></td>
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

        document.getElementById('so-form').addEventListener('submit', function (e) {
            if (!body.querySelector('tr')) {
                e.preventDefault();
                alert('Add at least one line item.');
            }
        });

        addRow();
    })();
</script>
@endpush
