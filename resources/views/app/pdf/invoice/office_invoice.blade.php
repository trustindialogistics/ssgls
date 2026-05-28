<!DOCTYPE html>
<html>

<head>
    <title>Bill - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        @page {
            margin: 6px;
            size: 297mm 210mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.8px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #111;
            padding: 2px 4px;
            vertical-align: top;
        }

        .no-border {
            border: 0 !important;
        }

        .invoice-shell {
            border: 1.5px solid #111;
            width: 100%;
        }

        .jurisdiction {
            font-size: 7.5px;
            line-height: 9px;
            margin-bottom: 2px;
            text-align: center;
            text-decoration: underline;
        }

        .master {
            border: 0;
            table-layout: fixed;
        }

        .master > tbody > tr > td,
        .master > tbody > tr > th {
            padding: 0;
        }

        .left-zone {
            width: 65%;
        }

        .right-zone {
            width: 35%;
        }

        .brand-row {
            height: 88px;
            table-layout: fixed;
        }

        .brand-row td {
            border-left: 0;
            border-right: 0;
            border-top: 0;
            padding: 2px 5px;
        }

        .logo-cell {
            text-align: center;
            width: 17%;
        }

        .company-logo {
            max-height: 72px;
            max-width: 112px;
        }

        .brand-fallback {
            color: #111;
            font-size: 31px;
            font-weight: bold;
            line-height: 27px;
            padding-top: 8px;
        }

        .brand-fallback span {
            display: block;
            font-size: 8px;
            letter-spacing: 0;
            line-height: 9px;
        }

        .company-cell {
            text-align: center;
            width: 63%;
        }

        .company-name {
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 24px;
            font-weight: bold;
            line-height: 24px;
            margin-top: 1px;
        }

        .company-tagline {
            font-size: 11.5px;
            font-weight: bold;
            line-height: 12px;
        }

        .company-address {
            font-size: 8.4px;
            line-height: 9.8px;
            margin-top: 4px;
            text-align: left;
        }

        .contact-cell {
            font-size: 9px;
            line-height: 11px;
            text-align: right;
            white-space: nowrap;
            width: 20%;
        }

        .branch-box {
            height: 82px;
            line-height: 10px;
            padding: 4px 6px !important;
        }

        .tax-box {
            height: 30px;
            padding: 5px 6px !important;
        }

        .tax-box div {
            font-size: 12px;
            font-weight: bold;
            line-height: 13px;
        }

        .party-box {
            height: 84px;
            padding: 0 !important;
            position: relative;
        }

        .party-head {
            height: 16px;
            table-layout: fixed;
        }

        .party-head td {
            border-bottom: 0;
            border-left: 0;
            border-right: 0;
            border-top: 0;
            font-size: 8.8px;
            height: 16px;
            padding: 2px 8px;
        }

        .party-line {
            height: 18px;
            line-height: 13px;
            padding: 3px 8px 1px;
        }

        .party-gstin {
            border-top: 1px solid #111;
            bottom: 0;
            height: 18px;
            line-height: 16px;
            padding: 1px 8px;
            position: absolute;
            width: 100%;
        }

        .bill-details td {
            font-size: 8.8px;
            height: 15px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .payment-table th,
        .payment-table td {
            font-size: 7.6px;
            height: 14px;
            padding: 1px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .basis-row {
            font-size: 8.8px;
            height: 23px;
            line-height: 11px;
            padding: 2px 6px !important;
        }

        .items th {
            font-size: 7.8px;
            font-weight: normal;
            height: 16px;
            line-height: 8.8px;
            padding: 1px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .items .group-head th {
            height: 18px;
        }

        .items td {
            font-size: 8.8px;
            height: 19px;
            padding: 2px 3px;
            text-align: center;
            vertical-align: top;
        }

        .items .blank-fill td {
            border-top: 0;
        }

        .words-row td {
            font-size: 9px;
            height: 22px;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .grand-label {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }

        .footer td {
            font-size: 9px;
            padding: 3px 6px;
        }

        .footer-head td {
            height: 20px;
        }

        .footer-body td {
            height: 58px;
        }

        .terms {
            font-size: 10px !important;
            line-height: 13px;
        }

        .prepared {
            font-size: 8.2px;
            vertical-align: bottom !important;
        }

        .emp-box {
            border: 1px solid #111;
            display: inline-block;
            height: 38px;
            line-height: 12px;
            margin-top: 4px;
            padding-top: 9px;
            text-align: center;
            width: 52px;
        }

        .for-company {
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: bold;
            line-height: 14px;
            text-align: center;
        }

        .signature {
            font-size: 12px;
            line-height: 14px;
            margin-top: 20px;
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

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
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

    $companyName = $invoice->company?->name ?: 'S S Gujarat Logistics Services';
    $billingBranch = $invoiceField(['billing_branch_name_address', 'billing_branch_address', 'billing_branch']);
    $panNo = $invoiceField(['pan_no', 'pan']) ?: $customerField(['pan_no', 'pan']) ?: 'BHLPS2943H';
    $companyGstin = $invoiceField(['gstin', 'gst_no']) ?: '24BHLPS2943H1Z3';
    $partyGstin = $invoice->customer->tax_id ?: $customerField(['gstin', 'gst_no']);
    $partyCode = $invoiceField(['party_code']);
    $branchCode = $invoiceField(['branch_code']);
    $tickBillType = $invoiceField(['tick_bill_type', 'bill_type']);
    $basisOfCharges = $invoiceField(['basis_of_charges', 'basis']);
    $enclosures = $invoiceField(['enclosures']);
    $gstTaxThrough = $invoiceField(['gst_tax_through', 'service_tax_through']) ?: 'CONSIGNOR / CONSIGNEE /';
    $empCode = $invoiceField(['emp_code', 'employee_code']);
    $preparedBy = $invoiceField(['prepared_by']);
    $checkedBy = $invoiceField(['checked_by']);
    $partyAddressHtml = preg_replace('/<br\s*\/?>/i', "\n", (string) $billing_address);
    $partyAddressHtml = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $partyAddressHtml);
    $partyAddressHtml = preg_replace('/<\/?p[^>]*>/i', "\n", $partyAddressHtml);
    $partyAddressText = html_entity_decode(strip_tags($partyAddressHtml), ENT_QUOTES, 'UTF-8');
    $partyAddressLines = collect(preg_split('/\r\n|\r|\n/', $partyAddressText))
        ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
        ->filter()
        ->values();

    if ($partyAddressLines->isEmpty() && $invoice->customer?->name) {
        $partyAddressLines = collect([$invoice->customer->name]);
    }
    $companyPhone = $invoice->company?->address?->phone;
    $companyEmail = \App\Models\CompanySetting::getSetting('notification_email', $invoice->company_id);
    $mobile = $invoiceField(['mobile', 'phone']) ?: ($companyPhone ?: '7600475900 6355071130');
    $email = $invoiceField(['email']) ?: ($companyEmail ?: 'ssgl2026@gmail.com');
    $displayCompanyAddress = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $company_address);
    $displayCompanyAddress = preg_replace('/<p[^>]*>\s*(?:<strong>)?\s*\(?A Cost Effective Distribution\)?\s*(?:<\/strong>)?\s*<\/p>/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/<br\s*\/?>\s*\(?A Cost Effective Distribution\)?/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/\(?A Cost Effective Distribution\)?/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*E-?mail\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*Mob(?:ile)?\.?\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = str_ireplace('Param Logistics And Industrial Pack', 'Param Logistics And Industrial Park', $displayCompanyAddress);
    $officeGrandTotal = 0;
    $blankRows = max(1, 11 - $invoice->items->count());
    $blankFillHeight = max(210, $blankRows * 21);
@endphp

    <div class="invoice-shell">
        <table class="master">
            <tr>
                <td class="left-zone">
                    <table class="brand-row">
                        <tr>
                            <td class="logo-cell">
                                <div class="jurisdiction">Subject to Vapi Jurisdiction</div>
                                @if ($logo)
                                    <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-fallback">SS<span>GUJARAT<br>LOGISTICS SERVICES</span></div>
                                @endif
                            </td>
                            <td class="company-cell">
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">(A Cost Effective Distribution)</div>
                                <div class="company-address">{!! $displayCompanyAddress !!}</div>
                            </td>
                            <td class="contact-cell">
                                Mob. {{ $mobile }}<br>
                                E-mail : {{ $email }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="right-zone branch-box">
                    Billing Br. Name &amp; Address :<br>{!! nl2br(e($billingBranch)) !!}
                </td>
            </tr>

            <tr>
                <td rowspan="4" class="party-box">
                    <table class="party-head">
                        <tr>
                            <td width="50%">Party Name &amp; Address :</td>
                            <td>Party Code : {{ $partyCode }}</td>
                        </tr>
                    </table>
                    @for ($lineIndex = 0; $lineIndex < 3; $lineIndex++)
                        <div class="party-line">{{ $partyAddressLines->get($lineIndex) ?: "\u{00A0}" }}</div>
                    @endfor
                    <div class="party-gstin">GSTIN : {{ $partyGstin }}</div>
                </td>
                <td class="tax-box">
                    <div>PAN No.: {{ $panNo }}</div>
                    <div>GSTIN : {{ $companyGstin }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="bill-details">
                        <tr>
                            <td width="50%">Bill No.: {{ $invoice->invoice_number }}</td>
                            <td>Branch Code : {{ $branchCode }}</td>
                        </tr>
                        <tr>
                            <td>Bill Date : {{ $invoice->formattedInvoiceDate }}</td>
                            <td>Payment Due Date : {{ $invoice->formattedDueDate }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="payment-table">
                        <tr>
                            <th rowspan="2" width="16%">Tick<br>Bill Type<br>{{ $tickBillType }}</th>
                            <th>Cash</th>
                            <th>Cheque No.</th>
                            <th>Date</th>
                            <th>Bank</th>
                            <th>Others</th>
                        </tr>
                        <tr>
                            <td>{{ $invoiceField(['cash']) }}</td>
                            <td>{{ $invoiceField(['cheque_no']) }}</td>
                            <td>{{ $invoiceField(['payment_date']) }}</td>
                            <td>{{ $invoiceField(['bank']) }}</td>
                            <td>{{ $invoiceField(['others']) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="basis-row">Basis of Charges as per G. C. Note or Contract No. : {{ $basisOfCharges }}</td>
            </tr>
        </table>

        <table class="items">
            <colgroup>
                <col style="width: 3%;">
                <col style="width: 7%;">
                <col style="width: 7%;">
                <col style="width: 8%;">
                <col style="width: 6.8%;">
                <col style="width: 6.8%;">
                <col style="width: 9%;">
                <col style="width: 5.5%;">
                <col style="width: 7.4%;">
                <col style="width: 10%;">
                <col style="width: 7.6%;">
                <col style="width: 7%;">
                <col style="width: 13.9%;">
            </colgroup>
            <thead>
                <tr class="group-head">
                    <th rowspan="2">Sl.<br>No.</th>
                    <th colspan="2">Consignment / Old Bill</th>
                    <th rowspan="2">Invoice<br>No.</th>
                    <th colspan="2">Destination</th>
                    <th rowspan="2">Vehicle No.</th>
                    <th rowspan="2">Pkg.</th>
                    <th rowspan="2">Charged<br>Weight Kgs.</th>
                    <th rowspan="2">Rate</th>
                    <th rowspan="2">Other Charge</th>
                    <th rowspan="2">DD Charge</th>
                    <th rowspan="2">Amount</th>
                </tr>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                    @php
                        $rate = $itemField($item, ['rate']);
                        $otherCharge = $itemField($item, ['other_charge']);
                        $ddCharge = $itemField($item, ['dd_charge']);
                        $calculatedAmount = null;

                        if ($rate !== '' || $otherCharge !== '' || $ddCharge !== '') {
                            $calculatedAmount = (int) round((
                                $numericField($rate)
                                + $numericField($otherCharge)
                                + $numericField($ddCharge)
                            ) * 100);
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
                        <td class="text-left">{{ $itemField($item, ['destination', 'to']) }}</td>
                        <td>{{ $itemField($item, ['vehicle_no', 'vehicle_number']) }}</td>
                        <td>{{ $itemField($item, ['pkg', 'package', 'packages']) ?: $item->quantity }}</td>
                        <td>{{ $itemField($item, ['charged_weight_kgs', 'charged_weight', 'weight']) }}</td>
                        <td class="text-right">{{ $rate ?: number_format($item->price / 100, 2) }}</td>
                        <td class="text-right">{{ $otherCharge }}</td>
                        <td class="text-right">{{ $ddCharge }}</td>
                        <td class="text-right">{!! format_money_pdf($officeLineTotal, $invoice->customer->currency) !!}</td>
                    </tr>
                @endforeach
                <tr class="blank-fill">
                    @for ($column = 0; $column < 13; $column++)
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                    @endfor
                </tr>
            </tbody>
        </table>

        @php
            $grandTotalForWords = $officeGrandTotal ?: $invoice->total;
            $rupeesInWords = $invoiceField(['rupees_in_words', 'amount_in_words']) ?: trim($numberToWords((int) floor($grandTotalForWords / 100)).' Rupees');
        @endphp

        <table class="words-row">
            <colgroup>
                <col style="width: 82%;">
                <col style="width: 8%;">
                <col style="width: 10%;">
            </colgroup>
            <tr>
                <td>Rupees in words : {{ $rupeesInWords }}</td>
                <td class="grand-label">GRAND TOTAL</td>
                <td class="text-right bold">{!! format_money_pdf($officeGrandTotal ?: $invoice->total, $invoice->customer->currency) !!}</td>
            </tr>
        </table>

        <table class="footer">
            <tr class="footer-head">
                <td width="42%" class="bold">Enclosures : {{ $enclosures }}</td>
                <td width="20%" colspan="2">GST Through : {{ $gstTaxThrough }}</td>
                <td width="38%" class="text-center">
                    <div class="for-company">For {{ $companyName }}</div>
                </td>
            </tr>
            <tr class="footer-body">
                <td width="42%" class="terms">
                    1) Payment should be made by payee A/c Cheque /<br>
                    &nbsp;&nbsp;&nbsp;D.D. Favour of {{ $companyName }}<br>
                    2) Interest @ 10% per annum will be charged if bill<br>
                    &nbsp;&nbsp;&nbsp;not paid within 7 days from date of bill
                </td>
                <td width="10%" class="prepared text-center">Prepared by :<br>{{ $preparedBy }}</td>
                <td width="10%" class="prepared text-center">Checked by :<br>{{ $checkedBy }}</td>
                <td width="38%">
                    <span class="emp-box">EMP Code<br>{{ $empCode }}</span>
                    <div class="signature">Signature</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
