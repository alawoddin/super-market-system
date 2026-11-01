<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; }
    </style>
</head>
<body>
    <h2>Profit & Loss Report</h2>
    <p>Generated: {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <tr><th>Sales</th><td>{{ number_format($report['gross_revenue'], 2) }} AFN</td></tr>
        <tr><th>Returns</th><td>- {{ number_format($report['returns'], 2) }} AFN</td></tr>
        <tr><th>Net Sales Revenue</th><td>{{ number_format($report['revenue'], 2) }} AFN</td></tr>
        <tr><th>Discounts</th><td>{{ number_format($report['discounts'], 2) }} AFN</td></tr>
        <tr><th>COGS</th><td>{{ number_format($report['cogs'], 2) }} AFN</td></tr>
        <tr><th>Gross Profit</th><td>{{ number_format($report['profit'], 2) }} AFN</td></tr>
        <tr><th>Expenses</th><td>{{ number_format($report['expenses'], 2) }} AFN</td></tr>
        <tr><th>Net Profit</th><td><strong>{{ number_format($report['net_profit'], 2) }} AFN</strong></td></tr>
    </table>
</body>
</html>
