<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receiptNumber }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            color: #1A1E23;
            margin: 0;
            padding: 32px;
            font-size: 13px;
        }
        .sheet { max-width: 420px; margin: 0 auto; }
        .head { display: table; width: 100%; margin-bottom: 20px; }
        .head-logo { display: table-cell; width: 64px; vertical-align: top; }
        .head-logo img { width: 56px; height: 56px; object-fit: contain; }
        .head-biz { display: table-cell; vertical-align: top; padding-left: 12px; }
        .biz-name { font-size: 19px; font-weight: 700; letter-spacing: 0.02em; margin: 0 0 4px; }
        .biz-line { font-size: 11px; color: #565F6A; line-height: 1.5; }
        .divider { border: none; border-top: 2px solid #1A1E23; margin: 16px 0; }
        .divider-light { border: none; border-top: 1px solid #D8DCE1; margin: 14px 0; }
        .eyebrow { font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: #8D96A0; margin: 0 0 2px; }
        .title-row { display: table; width: 100%; margin-bottom: 4px; }
        .title-row .t { display: table-cell; }
        .title-row .r { display: table-cell; text-align: right; }
        .receipt-title { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin: 0; }
        .receipt-no { font-family: 'DejaVu Sans Mono', monospace; font-size: 12px; }
        table.rows { width: 100%; border-collapse: collapse; margin: 16px 0; }
        table.rows td { padding: 5px 0; font-size: 12.5px; vertical-align: top; }
        table.rows td.label { color: #565F6A; width: 45%; }
        table.rows td.value { text-align: right; font-weight: 600; }
        .amount-box { background: #F2F4F6; border: 1px solid #D8DCE1; border-radius: 6px; padding: 14px 16px; margin: 18px 0; }
        .amount-row { display: table; width: 100%; }
        .amount-row .l { display: table-cell; font-size: 12px; color: #565F6A; text-transform: uppercase; letter-spacing: 0.06em; vertical-align: middle; }
        .amount-row .v { display: table-cell; text-align: right; font-size: 22px; font-weight: 700; vertical-align: middle; }
        .balance-note { font-size: 11px; color: #D64545; margin-top: 6px; text-align: right; }
        .footer { margin-top: 24px; text-align: center; font-size: 11px; color: #8D96A0; line-height: 1.6; }
        .print-bar { max-width: 420px; margin: 0 auto 16px; text-align: right; }
        .print-btn {
            display: inline-block; font-family: Arial, sans-serif; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 16px; border-radius: 6px;
            background: #22B0AB; color: #FFFFFF; text-decoration: none; border: none; cursor: pointer;
        }
        @media print {
            .print-bar { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    @unless ($forPdf)
        <div class="print-bar">
            <button class="print-btn" onclick="window.print()">Print</button>
            <a class="print-btn" style="margin-left:8px;" href="{{ route('payments.receipt.pdf', $payment) }}">Download PDF</a>
        </div>
    @endunless

    <div class="sheet">
        <div class="head">
            <div class="head-logo">
                @if ($gym->logo_path)
                    <img src="{{ $forPdf ? public_path('storage/'.$gym->logo_path) : $gym->logo_url }}" alt="">
                @endif
            </div>
            <div class="head-biz">
                <p class="biz-name">{{ $gym->name }}</p>
                <p class="biz-line">
                    @if ($gym->address){{ $gym->address }}<br>@endif
                    @if ($gym->phone){{ $gym->phone }}@endif
                    @if ($gym->phone && $gym->email) &middot; @endif
                    @if ($gym->email){{ $gym->email }}@endif
                    @if ($gym->website)<br>{{ $gym->website }}@endif
                    @if ($gym->tax_id)<br>Tax ID: {{ $gym->tax_id }}@endif
                </p>
            </div>
        </div>

        <hr class="divider">

        <div class="title-row">
            <div class="t"><p class="receipt-title">Payment Receipt</p></div>
            <div class="r"><span class="receipt-no">{{ $receiptNumber }}</span></div>
        </div>
        <p class="eyebrow">{{ $payment->paid_at->format('D, M j Y') }}</p>

        <table class="rows">
            <tr>
                <td class="label">Member</td>
                <td class="value">{{ $member->name }} ({{ $member->display_code }})</td>
            </tr>
            <tr>
                <td class="label">Plan</td>
                <td class="value">{{ $plan->name }}</td>
            </tr>
            <tr>
                <td class="label">Membership period</td>
                <td class="value">{{ $membership->start_date->format('M j, Y') }} &ndash; {{ $membership->end_date->format('M j, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Payment method</td>
                <td class="value" style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->method) }}</td>
            </tr>
            @if ($payment->note)
                <tr>
                    <td class="label">Note</td>
                    <td class="value">{{ $payment->note }}</td>
                </tr>
            @endif
        </table>

        <div class="amount-box">
            <div class="amount-row">
                <div class="l">Amount paid</div>
                <div class="v">{{ $gym->currency_symbol }}{{ number_format($payment->amount, 2) }}</div>
            </div>
            @if ($membership->balance_due > 0.01)
                <p class="balance-note">Remaining balance: {{ $gym->currency_symbol }}{{ number_format($membership->balance_due, 2) }}</p>
            @endif
        </div>

        <hr class="divider-light">

        <div class="footer">
            @if ($gym->receipt_footer)
                <p>{{ $gym->receipt_footer }}</p>
            @endif
            <p>Generated {{ now()->format('M j, Y g:ia') }}</p>
        </div>
    </div>
</body>
</html>
