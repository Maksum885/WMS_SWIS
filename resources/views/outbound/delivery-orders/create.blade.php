@extends('layouts.app')
@section('title', 'New Delivery Order')

@section('content')
    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title">New Delivery Order</div>
            </div>
        </div>

        @if (! $selectedPicking)
            <form method="GET" class="filters">
                <div class="filter-field span-2">
                    <label for="picking_id">Prefill from Picking (optional)</label>
                    <select name="picking_id" id="picking_id" class="filter-select">
                        <option value="">— Manual entry, no Picking —</option>
                        @foreach ($openPickings as $p)
                            <option value="{{ $p->id }}">{{ $p->picking_no }} — {{ $p->salesOrder->customer->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="filter-btn filter-field-btn">Load</button>
            </form>
        @else
            <div class="note-info">Prefilled from Picking <strong class="mono">{{ $selectedPicking->picking_no }}</strong> — <a href="{{ route('outbound.delivery-orders.create') }}">switch to manual entry</a></div>
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

        <form method="POST" action="{{ route('outbound.delivery-orders.store') }}" id="do-form">
            @csrf
            @if ($selectedPicking)
                <input type="hidden" name="picking_id" value="{{ $selectedPicking->id }}">
            @endif
            <div class="form-grid">
                <div class="form-field">
                    <label for="do_no">DO No <span class="req">*</span></label>
                    <input type="text" name="do_no" id="do_no" value="{{ old('do_no', $nextNo) }}" class="form-input mono" required>
                </div>
                <div class="form-field span-2">
                    <label for="sales_order_id">Sales Order <span class="req">*</span></label>
                    @if ($selectedPicking)
                        <input type="text" class="form-input" value="{{ $selectedPicking->salesOrder->so_no ?? '' }} — {{ $selectedPicking->salesOrder->customer->name ?? '' }}" readonly>
                        <input type="hidden" name="sales_order_id" value="{{ $selectedPicking->sales_order_id }}">
                    @else
                        <select name="sales_order_id" id="sales_order_id" class="form-select" required>
                            <option value="">Select confirmed SO</option>
                            @foreach ($salesOrders as $so)
                                <option value="{{ $so->id }}" @selected(old('sales_order_id') == $so->id)>{{ $so->so_no }} — {{ $so->customer->name ?? '' }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="form-field">
                    <label for="delivery_date">Delivery Date <span class="req">*</span></label>
                    <input type="date" name="delivery_date" id="delivery_date" value="{{ old('delivery_date', now()->format('Y-m-d')) }}" class="form-input" required>
                </div>
                <div class="form-field">
                    <label for="vehicle_no">Vehicle No</label>
                    <input type="text" name="vehicle_no" id="vehicle_no" value="{{ old('vehicle_no') }}" class="form-input mono">
                </div>
                <div class="form-field">
                    <label for="driver_name">Driver Name</label>
                    <input type="text" name="driver_name" id="driver_name" value="{{ old('driver_name') }}" class="form-input">
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
                            <th>Part Code</th>
                            <th style="width:16%">Qty Delivered</th>
                            <th style="width:12%">UOM</th>
                            <th style="width:16%">Lot No</th>
                            @unless ($selectedPicking)
                                <th style="width:6%"></th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody id="lines-body">
                        @if ($selectedPicking)
                            @foreach ($prefillLines as $i => $line)
                                <tr>
                                    <td>
                                        <input type="hidden" name="lines[{{ $i }}][matrix_partcode_id]" value="{{ $line['matrix_partcode_id'] }}">
                                        <span class="mono">{{ $line['partcode'] }}</span>
                                        <span class="muted-sub">{{ $line['model_name'] }}</span>
                                    </td>
                                    <td><input type="number" name="lines[{{ $i }}][qty_delivered]" step="0.01" min="0.01" value="{{ $line['qty'] }}" required></td>
                                    <td><input type="text" name="lines[{{ $i }}][uom]" value="{{ $line['uom'] }}" required></td>
                                    <td><input type="text" name="lines[{{ $i }}][lot_no]" value="{{ $line['lot_no'] }}"></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            @unless ($selectedPicking)
                <div class="line-items-foot">
                    <button type="button" class="add-line-btn" id="add-line">+ Add Line</button>
                </div>
            @endunless

            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Delivery Order</button>
                <a href="{{ route('outbound.delivery-orders.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    @unless ($selectedPicking)
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
                <td><input type="number" name="lines[__i__][qty_delivered]" step="0.01" min="0.01" required></td>
                <td><input type="text" name="lines[__i__][uom]" value="Pcs" required></td>
                <td><input type="text" name="lines[__i__][lot_no]"></td>
                <td class="ta-right"><button type="button" class="btn-danger-ghost remove-line">Remove</button></td>
            </tr>
        </template>
    @endunless
@endsection

@push('scripts')
<script>
    (function () {
        const body = document.getElementById('lines-body');
        const tplEl = document.getElementById('line-row-template');
        const form = document.getElementById('do-form');

        if (tplEl) {
            const tpl = tplEl.innerHTML;
            let i = 0;
            const addRow = function () {
                const html = tpl.replaceAll('__i__', i++);
                body.insertAdjacentHTML('beforeend', html);
            };
            document.getElementById('add-line').addEventListener('click', addRow);
            body.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-line')) {
                    e.target.closest('tr').remove();
                }
            });
            addRow();
        }

        form.addEventListener('submit', function (e) {
            if (!body.querySelector('tr')) {
                e.preventDefault();
                alert('Add at least one line item.');
            }
        });
    })();
</script>
@endpush
