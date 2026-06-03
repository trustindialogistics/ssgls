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
            border: 1.5px solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }

        .no-border {
            border: 0 !important;
        }

        .invoice-shell {
            border: 2px solid #000;
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

        .right-zone table td:first-child,
        .right-zone table th:first-child {
            border-left: 0;
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
            overflow: hidden;
            padding: 4px 6px !important;
            white-space: normal;
            word-break: break-word;
        }

        .branch-label {
            font-size: 10px;
            font-weight: bold;
        }

        .branch-address {
            display: block;
            line-height: 11px;
            margin-top: 2px;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .tax-box {
            height: 30px;
            overflow: hidden;
            padding: 11px 6px 2px !important;
        }

        .tax-box div {
            font-size: 10.5px;
            font-weight: bold;
            line-height: 13px;
            overflow-wrap: anywhere;
            white-space: normal;
            word-break: break-all;
        }

        .party-box {
            border-top: 0 !important;
            height: 142px;
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
            font-size: 10px;
            height: 16px;
            padding: 2px 8px;
        }

        .party-address-lines {
            bottom: 18px;
            left: 0;
            line-height: 13px;
            overflow: hidden;
            padding: 2px 8px 1px;
            position: absolute;
            right: 0;
            top: 16px;
        }

        .party-display-name {
            font-weight: normal;
            margin-bottom: 2px;
        }

        .party-gstin {
            border-top: 1px solid #111;
            bottom: 0;
            font-size: 8px;
            height: 18px;
            line-height: 16px;
            overflow: hidden;
            padding: 1px 8px;
            position: absolute;
            right: auto;
            text-overflow: clip;
            white-space: nowrap;
            width: 55%;
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
            font-weight: bold;
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
            bottom: 2px;
            font-size: 12px;
            left: 72px;
            line-height: 14px;
            position: absolute;
            right: 0;
            text-align: center;
        }

        .signature-image {
            bottom: 16px;
            display: block;
            height: 42px;
            margin: 0;
            max-width: 210px;
            object-fit: contain;
            position: absolute;
            right: 22px;
        }

        .signature-cell {
            overflow: hidden;
            position: relative;
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

    $companyName = $invoice->company?->name ?: '';
    $companyInitials = collect(preg_split('/\s+/', trim($companyName)))
        ->filter()
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
    $billingBranch = $invoice->company?->billing_branch_name_address ?: $invoiceField(['billing_branch_name_address', 'billing_branch_address', 'billing_branch']);
    $billingBranchHtml = preg_replace('/<br\s*\/?>/i', "\n", (string) $billingBranch);
    $billingBranchHtml = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $billingBranchHtml);
    $billingBranchHtml = preg_replace('/<\/?p[^>]*>/i', "\n", $billingBranchHtml);
    $billingBranchText = html_entity_decode(strip_tags($billingBranchHtml), ENT_QUOTES, 'UTF-8');
    $billingBranchLines = collect(preg_split('/\r\n|\r|\n/', $billingBranchText))
        ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
        ->filter()
        ->values();
    $companyTagline = $invoice->company?->tagline ?: '';
    $companyGstin = $invoiceField(['gstin', 'gst_no']) ?: ($invoice->company?->gstin ?: '');
    $companyEnrollmentNo = $invoice->company?->enrollment_no ?: $invoiceField(['enrollment_no', 'enrollment']);
    $companyTaxIdentityLabel = $companyEnrollmentNo ? 'Enrollment No' : 'GSTIN';
    $companyTaxIdentityValue = $companyEnrollmentNo ?: $companyGstin;
    $panNo = $invoiceField(['pan_no', 'pan']) ?: ($invoice->company?->pan_no ?: '');
    $partyGstin = $invoice->customer->tax_id ?: $customerField(['gstin', 'gst_no']);
    $partyCode = $invoiceField(['party_code']);
    $branchCode = $invoiceField(['branch_code']);
    $tickBillType = $invoiceField(['tick_bill_type', 'bill_type']);
    $basisOfCharges = $invoiceField(['basis_of_charges', 'basis']);
    $enclosures = $invoiceField(['enclosures']);
    $gstTaxThrough = $invoiceField(['gst_tax_through', 'service_tax_through']);
    $empCode = $invoiceField(['emp_code', 'employee_code']);
    $preparedBy = $invoiceField(['prepared_by']);
    $checkedBy = $invoiceField(['checked_by']);
    $billingAddress = $invoice->customer?->billingAddress;
    $partyDisplayName = $billingAddress?->name ?: $invoice->customer?->display_name ?: $invoice->customer?->name;
    $partyAddressLines = collect();

    if ($billingAddress) {
        $cityState = collect([$billingAddress->city, $billingAddress->state])->filter()->implode(', ');
        $cityStateZip = collect([$cityState, $billingAddress->zip])->filter()->implode(' ');

        $partyAddressLines = collect([
            $billingAddress->address_street_1,
            $billingAddress->address_street_2,
            $cityStateZip,
            $billingAddress->country?->name,
        ])->filter()->values();
    }

    if ($partyAddressLines->isEmpty()) {
        $partyAddressHtml = preg_replace('/<br\s*\/?>/i', "\n", (string) $billing_address);
        $partyAddressHtml = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $partyAddressHtml);
        $partyAddressHtml = preg_replace('/<\/?p[^>]*>/i', "\n", $partyAddressHtml);
        $partyAddressText = html_entity_decode(strip_tags($partyAddressHtml), ENT_QUOTES, 'UTF-8');
        $partyAddressLines = collect(preg_split('/\r\n|\r|\n/', $partyAddressText))
            ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
            ->reject(fn ($line) => $partyDisplayName && strcasecmp($line, $partyDisplayName) === 0)
            ->filter()
            ->values();

        if (! $partyDisplayName && $partyAddressLines->isNotEmpty()) {
            $partyDisplayName = $partyAddressLines->shift();
        }
    }
    $companyPhone = $invoice->company?->address?->phone;
    $companyEmail = $invoice->company?->notification_email ?: \App\Models\CompanySetting::getSetting('notification_email', $invoice->company_id);
    $mobile = $invoiceField(['mobile', 'phone']) ?: ($companyPhone ?: '');
    $email = $invoiceField(['email']) ?: ($companyEmail ?: '');
    $displayCompanyAddress = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $company_address);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*E-?mail\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*Mob(?:ile)?\.?\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    if ($companyPhone) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($companyPhone, '/').'\s*/i', '', $displayCompanyAddress);
    }
    if ($companyEmail) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($companyEmail, '/').'\s*/i', '', $displayCompanyAddress);
    }
    $officeGrandTotal = 0;
    $signaturePath = base_path('resources/static/img/PDF/authorized_signature.jpeg');
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
                                    <div class="brand-fallback">{{ $companyInitials }}</div>
                                @endif
                            </td>
                            <td class="company-cell">
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">{{ $companyTagline }}</div>
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
                    <span class="branch-label">Billing Br. Name & Address :</span>
                    <span class="branch-address">{!! nl2br(e($billingBranchLines->implode("\n"))) ?: '&nbsp;' !!}</span>
                </td>
            </tr>

            <tr>
                <td rowspan="4" class="party-box">
                    <table class="party-head">
                        <tr>
                            <td width="50%"><b>Party Name & Address :</b></td>
                            <td><b>Party Code :</b> {{ $partyCode }}</td>
                        </tr>
                    </table>
                    <div class="party-address-lines">
                        <div class="party-display-name">{{ $partyDisplayName }}</div>
                        {!! nl2br(e($partyAddressLines->implode("\n"))) ?: "\u{00A0}" !!}
                    </div>
                    <div class="party-gstin"><b>GSTIN :</b> {{ $partyGstin }}</div>
                </td>
                <td class="tax-box">
                    <div>PAN No.: {{ $panNo }}</div>
                    <div>{{ $companyTaxIdentityLabel }} : {{ $companyTaxIdentityValue }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="bill-details">
                        <tr>
                            <td width="50%"><b>Bill No.:</b> {{ $invoice->invoice_number }}</td>
                            <td><b>Branch Code :</b> {{ $branchCode }}</td>
                        </tr>
                        <tr>
                            <td><b>Bill Date :</b> {{ $invoice->formattedInvoiceDate }}</td>
                            <td><b>Payment Due Date :</b> {{ $invoice->formattedDueDate }}</td>
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
                <col style="width: 8.5%;">
                <col style="width: 6.8%;">
                <col style="width: 6.8%;">
                <col style="width: 6.8%;">
                <col style="width: 12.2%;">
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
                    <th rowspan="2">LR Charge</th>
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
                        $lrCharge = $itemField($item, ['lr_charge']);
                        $ddCharge = $itemField($item, ['dd_charge']);
                        $calculatedAmount = null;

                        if ($rate !== '' || $otherCharge !== '' || $lrCharge !== '' || $ddCharge !== '') {
                            $calculatedAmount = (int) round((
                                $numericField($rate)
                                + $numericField($otherCharge)
                                + $numericField($lrCharge)
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
                        <td class="text-right">{{ $lrCharge }}</td>
                        <td class="text-right">{{ $ddCharge }}</td>
                        <td class="text-right">{!! format_money_pdf($officeLineTotal, $invoice->customer->currency) !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $grandTotalForWords = $officeGrandTotal ?: $invoice->total;
            $rupeesInWords = $invoiceField(['rupees_in_words', 'amount_in_words']) ?: trim($numberToWords((int) floor($grandTotalForWords / 100)).' Rupees');
        @endphp

        <table class="words-row">
            <colgroup>
                <col style="width: 62%;">
                <col style="width: 20%;">
                <col style="width: 18%;">
            </colgroup>
            <tr>
                <td><b>Rupees in words :</b> {{ $rupeesInWords }}</td>
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
                <td width="38%" class="signature-cell">
                    <span class="emp-box">EMP Code<br>{{ $empCode }}</span>
                    @if (file_exists($signaturePath))
                        <img class="signature-image" src="{{ \App\Space\ImageUtils::toBase64Src($signaturePath) }}" alt="Signature">
                    @endif
                    <div class="signature">Signature</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
