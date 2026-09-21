@extends('layouts.app')
@section('title', $putaway->putaway_no)

@section('content')
    <a href="{{ route('inbound.putaways.index') }}" class="back-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to List
    </a>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <div class="report-panel-title mono">{{ $putaway->putaway_no }}</div>
                <div class="report-panel-caption">Put Away Confirmation</div>
            </div>
            <span class="status-tag status-{{ $putaway->status }}">{{ ucfirst($putaway->status) }}</span>
        </div>

        <div class="detail-grid">
            <div class="detail-field">
                <label>Source GRN</label>
                <div class="val">
                    @if ($putaway->grn)
                        <a href="{{ route('inbound.grns.show', $putaway->grn) }}" class="mono">{{ $putaway->grn->grn_no }}</a>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="detail-field">
                <label>Supplier</label>
                <div class="val">{{ $putaway->grn->supplier->name ?? '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Date</label>
                <div class="val">{{ optional($putaway->putaway_date)->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="detail-field">
                <label>Staff</label>
                <div class="val">{{ $putaway->created_by ?: '—' }}</div>
            </div>
        </div>

        <div class="detail-actions">
            <a href="{{ route('inbound.putaways.pdf', $putaway) }}" class="btn-secondary">Download PDF</a>
        </div>
    </div>

    <div class="section-title">Lines</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Component</th>
                    <th class="ta-right">Qty</th>
                    <th>Location</th>
                    <th>Confirmed At</th>
                    <th>Confirmed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($putaway->lines as $l)
                    <tr>
                        <td>
                            <span class="mono">{{ $l->grnLine->component ?? '—' }}</span>
                            <span class="muted-sub">{{ $l->grnLine->component_name ?? '' }}</span>
                        </td>
                        <td class="ta-right mono">{{ fmt_qty($l->qty) }} {{ $l->grnLine->uom ?? '' }}</td>
                        <td class="mono">{{ $l->location_code }}</td>
                        <td class="mono">{{ optional($l->confirmed_at)->format('d M Y H:i') ?: '—' }}</td>
                        <td>{{ $l->confirmed_by ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty-msg">No lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
