<style>
    @font-face {
        font-family: 'Cairo';
        src: url('file://{{ resource_path('fonts/Cairo-Regular.ttf') }}') format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    @font-face {
        font-family: 'Cairo';
        src: url('file://{{ resource_path('fonts/Cairo-Bold.ttf') }}') format('truetype');
        font-weight: bold;
        font-style: normal;
    }

    body {
        font-family: 'Cairo', sans-serif;
        direction: rtl;
        text-align: right;
        color: #1e293b;
        font-size: 12px;
    }

    .header {
        background-color: #0f172a;
        color: #ffffff;
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    .header .brand {
        color: #f59e0b;
        font-size: 20px;
        font-weight: bold;
    }

    .header .subtitle {
        color: #cbd5e1;
        font-size: 10px;
    }

    .header .doc-title {
        font-size: 16px;
        font-weight: bold;
        margin-top: 8px;
    }

    .header .doc-subtitle {
        font-size: 11px;
        color: #cbd5e1;
        text-align: left;
        margin-top: 2px;
    }

    .card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 14px;
        page-break-inside: avoid;
    }

    .card-title {
        font-size: 11px;
        color: #64748b;
        font-weight: bold;
        margin-bottom: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table.data th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 10px;
        padding: 8px;
        border-bottom: 1px solid #e2e8f0;
        text-align: center;
    }

    table.data td {
        padding: 8px;
        font-size: 10px;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
    }

    .row {
        display: table;
        width: 100%;
    }

    .row .col {
        display: table-cell;
        width: 33%;
    }

    .label {
        font-size: 9px;
        color: #94a3b8;
    }

    .value {
        font-size: 13px;
        font-weight: bold;
        color: #0f172a;
    }

    .value.emerald { color: #059669; }
    .value.red { color: #dc2626; }

    .footer {
        margin-top: 24px;
        font-size: 9px;
        color: #94a3b8;
        text-align: center;
    }
</style>
