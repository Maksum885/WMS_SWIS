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
        .check-col { width: 34px; text-align: center; }
        .check-box { display: inline-block; width: 12px; height: 12px; border: 1px solid #1E2226; }

        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9.5px; font-weight: bold; }
        .status-completed { background: #D1FAE5; color: #065F46; }

        .summary { margin-top: 6px; margin-bottom: 40px; font-size: 10px; color: #5B6470; }

        .sign-box { display: table; width: 100%; margin-top: 30px; }
        .sign-col { display: table-cell; width: 50%; }
        .sign-label { font-size: 9.5px; color: #5B6470; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 40px; }
        .sign-line { border-top: 1px solid #1E2226; width: 200px; padding-top: 4px; font-size: 10px; color: #5B6470; }
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
                Picking No.<br><b>{{ $picking->picking_no }}</b>
            </div>
        </div>
    </header>

    <footer>
        WMS SWIS — Politeknik Negeri Batam &middot; Generated {{ now()->format('d M Y H:i') }}
    </footer>

    <h1>PICKING LIST</h1>
    <div class="doc-date">Document Date: {{ $picking->created_at->format('d M Y') }}</div>

    <div class="info-box">
        <div class="info-col">
            <div class="info-row"><div class="info-label">Source SO</div><div class="info-value">{{ $picking->salesOrder->so_no ?? '—' }}</div></div>
            <div class="info-row"><div class="info-label">Customer</div><div class="info-value">{{ $picking->salesOrder->customer->name ?? '—' }}</div></div>
            <div class="info-row"><div class="info-label">Status</div><div class="info-value"><span class="status-badge status-{{ $picking->status }}">{{ ucfirst($picking->status) }}</span></div></div>
        </div>
        <div class="info-col">
            <div class="info-row"><div class="info-label">Date</div><div class="info-value">{{ optional($picking->picking_date)->format('d M Y') ?: '—' }}</div></div>
            <div class="info-row"><div class="info-label">Staff</div><div class="info-value">{{ $picking->created_by ?: '—' }}</div></div>
        </div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Part Code</th>
                <th>Description</th>
                <th>Lot No</th>
                <th class="ta-right">Qty Picked</th>
                <th>Pick Location</th>
                <th class="check-col">Check</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($picking->lines as $l)
                <tr>
                    <td>{{ $l->component }}</td>
                    <td>{{ $l->component_name }}</td>
                    <td>{{ $l->lot_no ?: '—' }}</td>
                    <td class="ta-right">{{ fmt_qty($l->qty_picked) }} {{ $l->soLine->uom ?? '' }}</td>
                    <td>{{ $l->location_code ?: '—' }}</td>
                    <td class="check-col"><span class="check-box"></span></td>
                </tr>
            @empty
                <tr><td colspan="6">No lines.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">Total line items: {{ $picking->lines->count() }}</div>

    <div class="sign-box">
        <div class="sign-col">
            <div class="sign-label">Picked By</div>
            <div class="sign-line">Name / Date</div>
        </div>
        <div class="sign-col">
            <div class="sign-label">Checked By</div>
            <div class="sign-line">Name / Date</div>
        </div>
    </div>
</body>
</html>
