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

        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.lines th, table.lines td { border: 1px solid #D8DCDE; padding: 6px 8px; text-align: left; }
        table.lines th { background: #F7F8F8; font-size: 9.5px; text-transform: uppercase; }
        .ta-right { text-align: right; }

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
                Component List
            </div>
        </div>
    </header>

    <footer>
        WMS SWIS — Politeknik Negeri Batam &middot; Generated {{ now()->format('d M Y H:i') }}
    </footer>

    <h1>COMPONENT MASTER LIST</h1>
    <div class="doc-date">Document Date: {{ now()->format('d M Y') }}</div>

    <table class="lines">
        <thead>
            <tr>
                <th>Component Code</th>
                <th>Component Name</th>
                <th>UOM</th>
                <th class="ta-right">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($components as $c)
                <tr>
                    <td>{{ $c->component_code }}</td>
                    <td>{{ $c->component_name }}</td>
                    <td>{{ $c->default_uom }}</td>
                    <td class="ta-right">{{ fmt_qty($c->qty_label) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No components.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">Total components: {{ $components->count() }}</div>
</body>
</html>
