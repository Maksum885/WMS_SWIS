<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 90px 40px 60px 40px; }
        body { font-family: sans-serif; font-size: 11px; color: #1E2226; }

        .watermark {
            position: fixed;
            top: 380px;
            left: -120px;
            width: 800px;
            transform: rotate(-35deg);
            font-size: 64px;
            font-weight: bold;
            color: #1E2226;
            opacity: 0.06;
            text-align: center;
            letter-spacing: 4px;
            white-space: nowrap;
        }

        header { position: fixed; top: -70px; left: 0; right: 0; height: 70px; }
        .letterhead { display: table; width: 100%; border-bottom: 2px solid #1E2226; padding-bottom: 8px; }
        .letterhead-name { font-size: 15px; font-weight: bold; }
        .letterhead-sub { font-size: 10px; color: #5B6470; }
        .letterhead-doc-no { text-align: right; font-size: 10px; color: #5B6470; }
        .letterhead-doc-no b { font-size: 12px; color: #1E2226; }

        footer { position: fixed; bottom: -50px; left: 0; right: 0; height: 40px; border-top: 1px solid #D8DCDE; padding-top: 6px; font-size: 9px; color: #8A93A0; }

        h1 { font-size: 20px; margin: 6px 0 2px; letter-spacing: 1px; }
        .doc-date { color: #5B6470; margin-bottom: 16px; font-size: 10px; }

        .info-box { display: table; width: 100%; margin-bottom: 18px; border: 1px solid #D8DCDE; border-radius: 4px; }
        .info-col { display: table-cell; width: 50%; padding: 10px 14px; vertical-align: top; }
        .info-col:first-child { border-right: 1px solid #D8DCDE; }
        .info-row { margin-bottom: 6px; }
        .info-label { color: #5B6470; font-size: 9.5px; text-transform: uppercase; letter-spacing: .03em; }
        .info-value { font-size: 11px; }

        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.lines th, table.lines td { border: 1px solid #D8DCDE; padding: 6px 8px; text-align: left; }
        table.lines th { background: #F7F8F8; font-size: 9.5px; text-transform: uppercase; }
        .ta-right { text-align: right; }

        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9.5px; font-weight: bold; }
        .status-completed { background: #D1FAE5; color: #065F46; }

        .summary { margin-top: 6px; font-size: 10px; color: #5B6470; }
    </style>
</head>
<body>
    <div class="watermark">SWIS POLIBATAM</div>

    <header>
        <div class="letterhead">
            <div style="display:table-cell; width:60%;">
                <div class="letterhead-name">Politeknik Negeri Batam</div>
                <div class="letterhead-sub">Smart Warehouse Integrated System (SWIS) &middot; Batam, Kepulauan Riau</div>
            </div>
            <div style="display:table-cell; width:40%;" class="letterhead-doc-no">
                Put Away No.<br><b>{{ $putaway->putaway_no }}</b>
            </div>
        </div>
    </header>

    <footer>
        WMS SWIS — Politeknik Negeri Batam &middot; Generated {{ now()->format('d M Y H:i') }}
    </footer>

    <h1>PUT AWAY CONFIRMATION</h1>
    <div class="doc-date">Document Date: {{ $putaway->created_at->format('d M Y') }}</div>

    <div class="info-box">
        <div class="info-col">
            <div class="info-row"><div class="info-label">Source GRN</div><div class="info-value">{{ $putaway->grn->grn_no ?? '—' }}</div></div>
            <div class="info-row"><div class="info-label">Supplier</div><div class="info-value">{{ $putaway->grn->supplier->name ?? '—' }}</div></div>
            <div class="info-row"><div class="info-label">Status</div><div class="info-value"><span class="status-badge status-{{ $putaway->status }}">{{ ucfirst($putaway->status) }}</span></div></div>
        </div>
        <div class="info-col">
            <div class="info-row"><div class="info-label">Date</div><div class="info-value">{{ optional($putaway->putaway_date)->format('d M Y') ?: '—' }}</div></div>
            <div class="info-row"><div class="info-label">Staff</div><div class="info-value">{{ $putaway->created_by ?: '—' }}</div></div>
        </div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Component</th>
                <th>Description</th>
                <th class="ta-right">Qty</th>
                <th>Location</th>
                <th>Confirmed At</th>
                <th>Confirmed By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($putaway->lines as $l)
                <tr>
                    <td>{{ $l->grnLine->component ?? '—' }}</td>
                    <td>{{ $l->grnLine->component_name ?? '—' }}</td>
                    <td class="ta-right">{{ fmt_qty($l->qty) }} {{ $l->grnLine->uom ?? '' }}</td>
                    <td>{{ $l->location_code }}</td>
                    <td>{{ optional($l->confirmed_at)->format('d M Y H:i') ?: '—' }}</td>
                    <td>{{ $l->confirmed_by ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No lines.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">Total line items: {{ $putaway->lines->count() }}</div>
</body>
</html>
