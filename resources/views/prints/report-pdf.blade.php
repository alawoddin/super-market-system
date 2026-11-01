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
        <tr><th>Sales</th><td>{{ amount($report['gross_revenue']) }} AFN</td></tr>
        <tr><th>Returns</th><td>- {{ amount($report['returns']) }} AFN</td></tr>
        <tr><th>Net Sales Revenue</th><td>{{ amount($report['revenue']) }} AFN</td></tr>
        <tr><th>Discounts</th><td>{{ amount($report['discounts']) }} AFN</td></tr>
        <tr><th>COGS</th><td>{{ amount($report['cogs']) }} AFN</td></tr>
        <tr><th>Gross Profit</th><td>{{ amount($report['profit']) }} AFN</td></tr>
        <tr><th>Expenses</th><td>{{ amount($report['expenses']) }} AFN</td></tr>
        <tr><th>Net Profit</th><td><strong>{{ amount($report['net_profit']) }} AFN</strong></td></tr>
    </table>
</body>
</html>
