<!DOCTYPE html>
<html>

<head>
    <title>Bill - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        @page {
            margin: 5px;
            size: 297mm 210mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #000;
            font-family: "DejaVu Sans";
            font-size: 8.5px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }

        .no-border {
            border: 0;
        }

        .invoice-wrapper {
            border: 1.5px solid #000;
            page-break-inside: avoid;
            width: 100%;
        }

        .jurisdiction-bar {
            border-bottom: 1px solid #000;
            font-size: 8px;
            padding: 1px 4px;
        }

        .header-table {
            border-bottom: 1.5px solid #000;
            table-layout: fixed;
        }

        .header-table > tbody > tr > td {
            border: 0;
            padding: 0;
        }

        .header-left {
            border-right: 1.5px solid #000 !important;
            padding: 3px 6px !important;
            width: 66%;
        }

        .header-right {
            width: 34%;
        }

        .brand-logo-cell {
            border: 0;
            text-align: center;
            width: 62px;
        }

        .company-logo {
            max-height: 44px;
            max-width: 58px;
        }

        .brand-mark {
            color: #27324a;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: -4px;
            line-height: 34px;
            text-align: center;
            width: 62px;
        }

        .brand-small {
            display: block;
            font-size: 6px;
            letter-spacing: 0;
            line-height: 7px;
            margin-top: 2px;
        }

        .company-name {
            color: #26304a;
            font-size: 23px;
            font-weight: bold;
            line-height: 23px;
        }

        .company-tagline {
            font-size: 10px;
            font-weight: bold;
            line-height: 11px;
        }

        .company-address {
            font-size: 8px;
            line-height: 10px;
            margin-top: 2px;
        }

        .header-party {
            border: 0;
            border-left: 1px solid #000;
            font-size: 8.5px;
            line-height: 11px;
            padding-left: 7px;
            width: 245px;
        }

        .header-party-address {
            line-height: 11px;
            margin-top: 2px;
        }

        .mobile {
            border: 0;
            font-size: 8.5px;
            text-align: right;
            white-space: nowrap;
            width: 82px;
        }

        .right-row {
            border-bottom: 1px solid #000;
            font-size: 8.5px;
            min-height: 14px;
            padding: 2px 5px;
        }

        .pan-gstin {
            border-bottom: 1px solid #000;
            padding: 4px 5px;
        }

        .pan-gstin div {
            font-size: 11px;
            font-weight: bold;
            line-height: 13px;
        }

        .detail-table td {
            font-size: 8.5px;
            height: 14px;
            padding: 1px 5px;
            vertical-align: middle;
        }

        .payment-table td,
        .payment-table th {
            font-size: 8px;
            height: 11px;
            padding: 1px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .items-table th {
            font-size: 8px;
            font-weight: bold;
            height: 22px;
            line-height: 10px;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
        }

        .items-table td {
            font-size: 8.5px;
            height: 17px;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .blank-fill td {
            border-top: 0;
        }

        .gt-table {
            border-top: 1.5px solid #000;
        }

        .gt-table td {
            font-size: 8.5px;
            height: 17px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .gt-label {
            background: #e0e0e0;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }

        .footer-top {
            border-top: 1px solid #000;
        }

        .footer-top td {
            font-size: 8.5px;
            height: 17px;
            padding: 2px 6px;
        }

        .footer-bottom {
            border-top: 1px solid #000;
            table-layout: fixed;
        }

        .footer-bottom td {
            font-size: 8px;
            height: 44px;
            padding: 2px 6px;
            vertical-align: bottom;
        }

        .terms {
            line-height: 10px;
            vertical-align: top !important;
        }

        .emp-wrap {
            font-size: 8px;
            text-align: left;
        }

        .emp-label {
            display: inline-block;
            font-weight: bold;
            line-height: 23px;
            vertical-align: top;
            white-space: nowrap;
        }

        .emp-box {
            border: 1px solid #000;
            display: inline-block;
            height: 24px;
            margin-left: 5px;
            width: 54px;
        }

        .signature-line {
            border-top: 1px solid #000;
            font-size: 8.5px;
            margin-left: 88px;
            margin-top: 9px;
            padding-top: 2px;
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }
    </style>
</head>

<body>
@php
    $normalize = function ($value) {
        return strtoupper(trim(preg_replace('/[^A-Z0-9]+/i', '_', (string) $value), '_'));
    };

    $fieldValue = function ($fields, $keys) use ($normalize) {
        $keys = collect((array) $keys)->map($normalize);

        foreach ($fields as $field) {
            if (! $field->customField) {
                continue;
            }

            $candidates = collect([
                $field->customField->slug,
                $field->customField->name,
                $field->customField->label,
            ])->filter()->map($normalize);

            foreach ($keys as $key) {
                if (
                    $candidates->contains($key)
                    || $candidates->contains('CUSTOM_INVOICE_'.$key)
                    || $candidates->contains('CUSTOM_ITEM_'.$key)
                    || $candidates->contains('CUSTOM_CUSTOMER_'.$key)
                ) {
                    return $field->defaultAnswer;
                }
            }
        }

        return '';
    };

    $numericField = function ($value) {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    };

    $numberToWords = function ($number) use (&$numberToWords) {
        $number = (int) $number;

        if ($number === 0) {
            return 'Zero';
        }

        $ones = [
            '',
            'One',
            'Two',
            'Three',
            'Four',
            'Five',
            'Six',
            'Seven',
            'Eight',
            'Nine',
            'Ten',
            'Eleven',
            'Twelve',
            'Thirteen',
            'Fourteen',
            'Fifteen',
            'Sixteen',
            'Seventeen',
            'Eighteen',
            'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            return trim($tens[intdiv($number, 10)].' '.$ones[$number % 10]);
        }

        if ($number < 1000) {
            $remainder = $number % 100;

            return trim($ones[intdiv($number, 100)].' Hundred'.($remainder ? ' '.$numberToWords($remainder) : ''));
        }

        if ($number < 100000) {
            $remainder = $number % 1000;

            return trim($numberToWords(intdiv($number, 1000)).' Thousand'.($remainder ? ' '.$numberToWords($remainder) : ''));
        }

        if ($number < 10000000) {
            $remainder = $number % 100000;

            return trim($numberToWords(intdiv($number, 100000)).' Lakh'.($remainder ? ' '.$numberToWords($remainder) : ''));
        }

        $remainder = $number % 10000000;

        return trim($numberToWords(intdiv($number, 10000000)).' Crore'.($remainder ? ' '.$numberToWords($remainder) : ''));
    };

    $invoiceField = function ($keys) use ($invoice, $fieldValue) {
        return $fieldValue($invoice->fields, $keys);
    };

    $customerField = function ($keys) use ($invoice, $fieldValue) {
        return $invoice->customer ? $fieldValue($invoice->customer->fields, $keys) : '';
    };

    $itemField = function ($item, $keys) use ($fieldValue) {
        return $fieldValue($item->fields, $keys);
    };

    $companyName = $invoice->company?->name ?: 'S S Gujarat Logistics';
    $billingBranch = trim(strip_tags($company_address)) ? $companyName.'<br>'.$company_address : $companyName;
    $panNo = 'NBKPS0084L';
    $companyGstin = '24NBKPS0084L1ZZ';
    $partyGstin = $invoice->customer->tax_id ?? '';
    $branchCode = '';
    $basisOfCharges = '';
    $enclosures = '';
    $gstTaxThrough = $invoiceField(['gst_tax_through']) ?: 'CONSIGNOR / CONSIGNEE';
    $empCode = '';
    $preparedBy = '';
    $checkedBy = '';
    $signatureName = $invoiceField(['signature_name']);
    $partyAddress = trim(strip_tags($billing_address)) ? $billing_address : e($invoice->customer->name ?? '');
    $companyPhone = $invoice->company?->address?->phone;
    $mobile = $companyPhone ?: ($invoiceField(['mobile', 'phone']) ?: '6355071130');
    $email = $invoiceField(['email']) ?: 'ssglogistic2021@gmail.com';
    $blankRows = max(1, 11 - $invoice->items->count());
    $blankFillHeight = max(122, $blankRows * 17);
    $officeGrandTotal = 0;
@endphp

    <div class="invoice-wrapper">
        <div class="jurisdiction-bar">Subject to Umbergaon Jurisdiction</div>

        <table class="header-table">
            <tr>
                <td class="header-left">
                    <table>
                        <tr>
                            <td class="brand-logo-cell">
                                @if ($logo)
                                    <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-mark">
                                        SS
                                        <span class="brand-small">GUJARAT LOGISTICS</span>
                                    </div>
                                @endif
                            </td>
                            <td class="no-border">
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">(A Cost Effective Distribution)</div>
                                <div class="company-address">
                                    {!! $company_address !!}<br>
                                    E-mail : {{ $email }}
                                </div>
                            </td>
                            <td class="header-party">
                                <span class="bold">Party Name &amp; Address :</span>
                                <div class="header-party-address">{!! $partyAddress !!}</div>
                                <div><span class="bold">GSTIN :</span> {{ $partyGstin }}</div>
                            </td>
                            <td class="mobile">Mob. {{ $mobile }}</td>
                        </tr>
                    </table>
                </td>
                <td class="header-right">
                    <div class="right-row">
                        <span class="bold">Billing Br. Name &amp; Address :</span>
                        {!! $billingBranch !!}
                    </div>

                    <div class="pan-gstin">
                        <div>PAN No.: {{ $panNo }}</div>
                        <div>GSTIN : {{ $companyGstin }}</div>
                    </div>

                    <table class="detail-table">
                        <tr>
                            <td width="50%"><span class="bold">Bill No. :</span> {{ $invoice->invoice_number }}</td>
                            <td><span class="bold">Branch Code :</span> {{ $branchCode }}</td>
                        </tr>
                        <tr>
                            <td><span class="bold">Bill Date :</span> {{ $invoice->formattedInvoiceDate }}</td>
                            <td><span class="bold">Payment Due Date :</span> {{ $invoice->formattedDueDate }}</td>
                        </tr>
                    </table>

                    <table class="payment-table">
                        <tr>
                            <th rowspan="2" width="15%">Tick<br>Bill Type</th>
                            <th>Cash</th>
                            <th>Cheque No.</th>
                            <th>Date</th>
                            <th>Bank</th>
                            <th>Others</th>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>

                    <div class="right-row" style="border-bottom: 0;">
                        <span class="bold">Basis of Charges as per G. C. Note or Contract No. :</span> {{ $basisOfCharges }}
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="3%">Sl.<br>No.</th>
                    <th width="8%">Consignment / Old Bill<br>Number</th>
                    <th width="7%">Date</th>
                    <th width="8%">Invoice<br>No.</th>
                    <th width="8%">From</th>
                    <th width="9%">Destination</th>
                    <th width="9%">Vehicle No.</th>
                    <th width="5%">Pkg.</th>
                    <th width="8%">Charged<br>Weight Kgs.</th>
                    <th width="8%">Rate</th>
                    <th width="8%">Other Charge</th>
                    <th width="7%">LR Charge</th>
                    <th width="7%">DD Charge</th>
                    <th width="7%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                    @php
                        $rate = $itemField($item, ['rate']);
                        $otherCharge = $itemField($item, ['other_charge']);
                        $lrCharge = $itemField($item, ['lr_charge']);
                        $ddCharge = $itemField($item, ['dd_charge']);
                        $weight = $itemField($item, ['charged_weight_kgs', 'charged_weight', 'weight']);
                        $calculatedAmount = null;

                        if ($rate !== '' || $otherCharge !== '' || $lrCharge !== '' || $ddCharge !== '') {
                            $calculatedAmount = $numericField($rate) + $numericField($otherCharge) + $numericField($lrCharge) + $numericField($ddCharge);
                            $calculatedAmount = (int) round($calculatedAmount * 100);
                        }

                        $officeLineTotal = $calculatedAmount ?? $item->total;
                        $officeGrandTotal += $officeLineTotal;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $itemField($item, ['consignment_number', 'old_bill_number', 'consignment_old_bill_number']) ?: $item->name }}</td>
                        <td>{{ $itemField($item, ['consignment_date', 'old_bill_date', 'date']) }}</td>
                        <td>{{ $itemField($item, ['invoice_no', 'invoice_number']) ?: $invoice->invoice_number }}</td>
                        <td class="text-left">{{ $itemField($item, ['from']) }}</td>
                        <td class="text-left">{{ $itemField($item, ['destination']) }}</td>
                        <td>{{ $itemField($item, ['vehicle_no', 'vehicle_number']) }}</td>
                        <td>{{ $itemField($item, ['pkg', 'package', 'packages']) ?: $item->quantity }}</td>
                        <td>{{ $weight }}</td>
                        <td class="text-right">{{ $rate ?: number_format($item->price / 100, 2) }}</td>
                        <td class="text-right">{{ $otherCharge }}</td>
                        <td class="text-right">{{ $lrCharge }}</td>
                        <td class="text-right">{{ $ddCharge }}</td>
                        <td class="text-right">{!! format_money_pdf($officeLineTotal, $invoice->customer->currency) !!}</td>
                    </tr>
                @endforeach
                <tr class="blank-fill">
                    @for ($column = 0; $column < 14; $column++)
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                    @endfor
                </tr>
            </tbody>
        </table>

        @php
            $grandTotalForWords = $officeGrandTotal ?: $invoice->total;
            $rupeesInWords = trim($numberToWords((int) floor($grandTotalForWords / 100)).' Rupees');
        @endphp

        <table class="gt-table">
            <tr>
                <td width="72%"><span class="bold">Rupees in words :</span> {{ $rupeesInWords }}</td>
                <td width="14%" class="gt-label">GRAND TOTAL</td>
                <td width="14%" class="text-right bold">{!! format_money_pdf($officeGrandTotal ?: $invoice->total, $invoice->customer->currency) !!}</td>
            </tr>
        </table>

        <table class="footer-top">
            <tr>
                <td width="38%"><span class="bold">Enclosures :</span> {{ $enclosures }}</td>
                <td width="37%" class="text-center">GST Tax Through : {{ $gstTaxThrough }}</td>
                <td width="25%" class="text-center bold">For {{ $companyName }}</td>
            </tr>
        </table>

        <table class="footer-bottom">
            <tr>
                <td width="38%" class="terms">
                    1) Payment should be made by payee A/c Cheque /<br>
                    &nbsp;&nbsp;&nbsp;D.D. Favour of {{ $companyName }}<br>
                    2) Interest @ 10% per annum will be charged if bill<br>
                    &nbsp;&nbsp;&nbsp;not paid within 7 days from date of bill
                </td>
                <td width="37%">
                    <table>
                        <tr>
                            <td class="no-border" width="50%">
                                Prepared by : {{ $preparedBy }}
                            </td>
                            <td class="no-border">
                                Checked by : {{ $checkedBy }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="25%">
                    <div class="emp-wrap">
                        <span class="emp-label">EMP Code</span>
                        <span class="emp-box">{{ $empCode }}</span>
                    </div>
                    <div class="signature-line">Signature{{ $signatureName ? ' : '.$signatureName : '' }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
