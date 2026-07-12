<!DOCTYPE html>
<html>

<head>
    <title>LR Receipt - {{ $invoice->invoice_number }}</title>
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
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11.2px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1.5px solid #000;
            padding: 2px 5px;
            vertical-align: top;
        }

        .wrapper {
            page-break-inside: avoid;
            width: 100%;
        }

        .wrapper > table {
            border: 2px solid #000;
        }

        .no-border {
            border: 0;
        }

        .jurisdiction {
            font-size: 10.5px;
            line-height: 9px;
            margin-bottom: 2px;
            text-align: center;
            text-decoration: underline;
            white-space: nowrap;
        }

        .header-left {
            border-right: 1.5px solid #000 !important;
            padding: 0;
            vertical-align: top;
            width: 61%;
        }

        .header-right {
            padding: 0;
            vertical-align: top;
            width: 39%;
        }

        .logo-cell {
            text-align: center;
            width: 17%;
            vertical-align: top;
        }

        .company-logo {
            max-height: 72px;
            max-width: 112px;
            display: block;
            margin: 0 auto;
        }

        .brand-fallback {
            color: #111;
            font-size: 34px;
            font-weight: bold;
            line-height: 27px;
            padding-top: 8px;
            text-align: center;
        }

        .company-cell {
            text-align: center;
            width: 83%;
            vertical-align: top;
        }

        .company-name {
            color: #111;
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 27px;
            font-weight: bold;
            line-height: 24px;
            margin-top: 1px;
            text-align: center;
        }

        .company-tagline {
            font-size: 14.5px;
            font-weight: bold;
            line-height: 12px;
            text-align: center;
        }

        .company-address {
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 14.4px;
            font-weight: bold;
            line-height: 15px;
            margin-top: 4px;
            text-align: center;
        }

        .header-left table,
        .header-right table {
            border: 0;
        }

        .brand-block {
            height: auto;
        }

        .brand-block td {
            border: 0;
            padding: 2px 6px;
            vertical-align: top;
        }

        .top-detail-table td {
            font-size: 16px;
            height: 24px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .top-detail-table td .label {
            font-size: 11px;
        }

        .top-detail-table .tax-line {
            height: 32px;
        }

        .party-table {
            border-top: 2.5px solid #000 !important;
        }

        .party-table td {
            border-bottom: 0;
            border-top: 0;
        }

        .party-cell {
            height: 142px;
            padding: 4px 6px;
            width: 50%;
        }

        .party-cell > .label {
            font-size: 12.5px;
        }

        .party-lines {
            border-bottom: 1px solid #ddd;
            font-size: 18px;
            height: 31px;
            line-height: 29px;
            margin-top: 0;
            overflow: hidden;
        }

        .party-lines .label {
            font-size: 13px;
        }

        .party-details {
            font-size: 18px;
            height: 80px;
            line-height: 18px;
            padding-top: 4px;
        }

        .side-cell {
            padding: 0;
            width: 39%;
        }

        .side-table td {
            height: 20px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .docket-no {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0;
            text-align: left;
        }

        .owner-risk {
            font-size: 15px;
            font-weight: bold;
        }

        .tax-line {
            font-size: 19px;
            font-weight: bold;
            line-height: 17px;
            overflow-wrap: anywhere;
        }

        .tax-line .label {
            font-size: 14px;
        }

        .goods {
            border-top: 2.5px solid #000;
        }

        .goods td {
            font-size: 18px;
            height: 28px;
            line-height: 17px;
        }

        .goods .large {
            height: 45px;
        }

        .delivery-cell {
            font-size: 18px;
            line-height: 17px;
        }

        .eway-inline {
            border-top: 1.5px solid #000;
            margin: 15px -5px 0;
            padding: 3px 5px 0;
        }

        .left-panel {
            padding: 0;
            vertical-align: top;
            width: 100%;
        }

        .left-panel .label, .goods .label {
            font-size: 13px;
        }

        .freight-panel {
            padding: 0;
            vertical-align: top;
            width: 100%;
        }

        .charges th {
            font-size: 14px;
            font-weight: bold;
            height: 28px;
            line-height: 13px;
            text-align: center;
            vertical-align: middle;
        }

        .charges td {
            font-size: 19px;
            height: 25px;
            line-height: 19px;
        }

        .mode {
            font-size: 15px;
            font-weight: bold;
            line-height: 12px;
            text-align: center;
            vertical-align: middle;
        }

        .mode-struck {
            color: #666;
            text-decoration: line-through;
        }

        .mode-selected {
            border-bottom: 1px solid #111;
            padding-bottom: 1px;
        }

        .copy-label-box {
            font-size: 15px;
            height: 88px;
            line-height: 16px;
            padding: 8px 0 0 112px;
            text-align: left;
        }

        .goods-fill {
            height: 18px;
        }

        .footer-left {
            table-layout: fixed;
        }

        .footer-left td {
            padding: 0;
        }

        .declaration {
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 9px;
            line-height: 9.8px;
            height: 56px;
            overflow: hidden;
            padding: 3px 5px;
        }

        .agreement {
            border-top: 1.5px solid #000 !important;
            font-size: 12.4px;
            font-weight: bold;
            line-height: 12px;
            padding: 5px 2px !important;
            text-align: center;
        }

        .consignee-sign {
            height: 98px;
            line-height: 13px;
            padding: 4px 6px !important;
        }

        .gst-payable {
            font-size: 14px;
            font-weight: bold;
            height: 42px;
            line-height: 14px;
            padding-top: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .for-company {
            border-bottom: 0 !important;
            border-top: 1.5px solid #000 !important;
            font-size: 16px;
            font-weight: bold;
            height: 75px;
            line-height: 18px;
            padding-top: 6px;
            text-align: center;
        }

        .sig-container {
            width: 100%;
            text-align: center;
            margin-top: 4px;
        }

        .signature-image {
            display: inline-block;
            height: 38px;
            max-width: 180px;
            object-fit: contain;
        }

        .company-separator {
            display: none;
        }

        .label {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
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
                    || $candidates->contains('CUSTOM_Invoice_'.$key)
                    || $candidates->contains('CUSTOM_ITEM_'.$key)
                    || $candidates->contains('CUSTOM_Item_'.$key)
                    || $candidates->contains('CUSTOM_CUSTOMER_'.$key)
                    || $candidates->contains('CUSTOM_Customer_'.$key)
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

    $invoiceField = function ($keys) use ($invoice, $fieldValue) {
        return $fieldValue($invoice->fields, $keys);
    };

    $addressLines = function ($address) {
        if (! $address) {
            return [];
        }

        $cityState = collect([$address->city, $address->state])->filter()->implode(', ');
        $cityStateZip = collect([$cityState, $address->zip])->filter()->implode(' ');

        return collect([
            $address->name,
            $address->address_street_1,
            $address->address_street_2,
            $cityStateZip,
        ])->filter()->values()->all();
    };

    $partyDetails = function ($customer, $fallback = '') use ($addressLines) {
        if (! $customer) {
            return $fallback;
        }

        $name = $customer->name ?: $customer->display_name;
        $address = $customer->billingAddress ?: $customer->shippingAddress;
        $lines = collect([$name])
            ->merge($addressLines($address))
            ->filter()
            ->unique()
            ->take(4)
            ->values();

        return $lines->isNotEmpty() ? $lines->implode("\n") : $fallback;
    };

    $fitPartyText = function ($value) {
        return collect(preg_split('/\R/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->take(4)
            ->implode("\n");
    };

    $item = $invoice->items->first();
    $itemField = function ($keys) use ($item, $fieldValue) {
        return $item ? $fieldValue($item->fields, $keys) : '';
    };

    $moneyText = function ($paise) use ($invoice) {
        return $paise ? format_money_pdf((int) round($paise), $invoice->customer->currency) : '';
    };

    $companyName = $invoice->company?->name ?: '';
    $companyInitials = collect(preg_split('/\s+/', trim($companyName)))
        ->filter()
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
    $companyTagline = $invoice->company?->tagline ?: '';
    $companyTopHeading = $invoice->company?->top_heading ?: 'Subject to Vapi Jurisdiction';
    $companyAddress = trim(strip_tags($company_address)) ? $company_address : '';
    $companyPhone = $invoice->company?->address?->phone;
    $companyEmail = \App\Models\CompanySetting::getSetting('notification_email', $invoice->company_id);
    $mobile = $companyPhone ?: ($invoiceField(['mobile', 'phone']) ?: '');
    $email = $invoiceField(['email']) ?: ($companyEmail ?: '');
    $displayCompanyAddress = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $companyAddress);
    if ($companyName) {
        $cleanNamePattern = '/^\s*(?:<[^>]+>)*\s*' . preg_quote($companyName, '/') . '\s*(?:<\/[^>]+>)*\s*(?:<br\s*\/?>)?/i';
        $displayCompanyAddress = preg_replace($cleanNamePattern, '', $displayCompanyAddress);
    }
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*E-?mail\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*Mob(?:ile)?\.?\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    if ($companyPhone) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($companyPhone, '/').'\s*/i', '', $displayCompanyAddress);
    }
    if ($companyEmail) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($companyEmail, '/').'\s*/i', '', $displayCompanyAddress);
    }
    $panNo = $invoiceField(['pan_no', 'pan']) ?: ($invoice->company?->pan_no ?: '');
    $companyGstin = $invoiceField(['gstin', 'gst_no']) ?: ($invoice->company?->gstin ?: '');
    $companyEnrollmentNo = $invoice->company?->enrollment_no ?: $invoiceField(['enrollment_no', 'enrollment']);
    $companyTaxIdentityLabel = $companyEnrollmentNo ? 'Enrollment No' : 'GSTIN';
    $companyTaxIdentityValue = $companyEnrollmentNo ?: $companyGstin;

    $basicFreight = $itemField(['basic_freight']);
    $localCollection = $itemField(['local_collection']);
    $doorDelivery = $itemField(['door_delivery']);
    $hamali = $itemField(['hamali']);
    $docketCharge = $itemField(['docket_charge']) ?: 100;
    $otherCharge = $itemField(['other_charge']);
    $fov = $itemField(['fov']);
    $netAmount = (
        $numericField($basicFreight)
        + $numericField($localCollection)
        + $numericField($doorDelivery)
        + $numericField($hamali)
        + $numericField($docketCharge)
        + $numericField($otherCharge)
        + $numericField($fov)
    ) * 100;

    $modeOfPayment = $invoiceField(['mode_of_payment']) ?: 'TO PAY';
    $selectedMode = $normalize($modeOfPayment);
    $modeLabel = function (string $label) use ($normalize, $selectedMode) {
        if ($normalize($label) === $selectedMode) {
            return '<span class="mode-selected">'.e($label).'</span>';
        }

        return '<span class="mode-struck">'.e($label).'</span>';
    };
    $formatAddress = function ($customer) {
        if (!$customer) return '';
        $billing = $customer->billingAddress;
        if (!$billing) return '';
        $lines = [
            $billing->address_street_1,
            $billing->address_street_2,
            implode(', ', array_filter([$billing->city, $billing->state])) . ($billing->zip ? ' ' . $billing->zip : '')
        ];
        return implode("\n", array_filter(array_map('trim', $lines)));
    };

    $parseParty = function ($partyText) {
        $lines = collect(explode("\n", (string) $partyText))
            ->map(fn($line) => trim($line))
            ->filter()
            ->values();

        $name = $lines->first() ?: '';
        $addressLines = $lines->slice(1)->values()->all();

        return [
            'name' => $name,
            'address' => implode("\n", $addressLines),
        ];
    };

    $gstPayableBy = $invoiceField(['gst_tax_payable_by']) ?: 'Consignor / Consignee';

    $consignorName = $invoice->customer ? $invoice->customer->name : $parseParty($invoiceField(['consignor']))['name'];
    $consignorAddress = $invoice->customer ? $formatAddress($invoice->customer) : $parseParty($invoiceField(['consignor']))['address'];
    $consignorPhone = ($invoice->customer && $invoice->customer->phone) ? $invoice->customer->phone : $invoiceField(['consignor_phone_no']);
    $consignorGstin = ($invoice->customer && $invoice->customer->tax_id) ? $invoice->customer->tax_id : $invoiceField(['consignor_gst_no']);

    $consigneeName = $invoice->consigneeCustomer ? $invoice->consigneeCustomer->name : $parseParty($invoiceField(['consignee']))['name'];
    $consigneeAddress = $invoice->consigneeCustomer ? $formatAddress($invoice->consigneeCustomer) : $parseParty($invoiceField(['consignee']))['address'];
    $consigneePhone = ($invoice->consigneeCustomer && $invoice->consigneeCustomer->phone) ? $invoice->consigneeCustomer->phone : $invoiceField(['consignee_phone_no']);
    $consigneeGstin = ($invoice->consigneeCustomer && $invoice->consigneeCustomer->tax_id) ? $invoice->consigneeCustomer->tax_id : $invoiceField(['consignee_gst_no']);

    $docketNumber = $invoice->invoice_number;
    $descriptionOfGoods = trim((string) $itemField(['description_of_goods']));
    $noOfArticles = trim((string) $itemField(['no_of_articles']));

    if (preg_match('/^LR Receipt\s+\d+$/i', $descriptionOfGoods)) {
        $descriptionOfGoods = '';
    }

    if ($noOfArticles === '1' && $descriptionOfGoods === '') {
        $noOfArticles = '';
    }
    $signaturePath = base_path('resources/static/img/PDF/authorized_signature.jpeg');

    $consignorData = [
        'name' => $consignorName,
        'address' => $consignorAddress,
    ];
    $consigneeData = [
        'name' => $consigneeName,
        'address' => $consigneeAddress,
    ];

    $isMulti = request()->has('multi') || request()->query('copy') === 'multi';

    if ($isMulti) {
        $renderCopies = [
            [
                'key' => 'consignee',
                'label' => 'CONSIGNEE COPY',
                'bg' => '#ffffff',
            ],
            [
                'key' => 'driver',
                'label' => 'DRIVER COPY',
                'bg' => '#eafaf1',
            ],
            [
                'key' => 'consignor',
                'label' => 'CONSIGNOR COPY',
                'bg' => '#fdf2f8',
            ]
        ];
    } else {
        $currentCopy = request()->query('copy');
        $bg = '#ffffff';
        if ($currentCopy === 'driver') {
            $bg = '#eafaf1';
        } elseif ($currentCopy === 'consignor') {
            $bg = '#fdf2f8';
        } elseif ($currentCopy === 'ho') {
            $bg = '#fefde7';
        }

        $renderCopies = [
            [
                'key' => $currentCopy ?: 'default',
                'label' => $copyLabel ?: '',
                'bg' => $bg,
            ]
        ];
    }
@endphp

@foreach ($renderCopies as $index => $copy)
    <div class="wrapper" style="background-color: {{ $copy['bg'] }}; @if(!$loop->last) page-break-after: always; margin-bottom: 20px; @endif">
        <table>
            <tr>
                <td class="header-left">
                    <table class="brand-block">
                        <tr>
                            <td class="logo-cell">
                                <div class="jurisdiction">{{ $companyTopHeading }}</div>
                                @if ($logo)
                                    <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-fallback">{{ $companyInitials }}</div>
                                @endif
                            </td>
                            <td class="company-cell">
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">{{ $companyTagline }}</div>
                                <div class="company-address">
                                    {!! $displayCompanyAddress !!}<br>
                                    Mob. {{ $mobile }} &nbsp;&nbsp;&nbsp;&nbsp; E-mail : {{ $email }}
                                </div>
                            </td>
                        </tr>
                    </table>

                    <table class="party-table">
                        <tr>
                            <td class="party-cell">
                                <span class="label">Consignor</span> <span style="font-weight: bold; font-size: 16px; padding-left: 5px;">{{ $consignorData['name'] }}</span>
                                <div class="party-lines party-details">{!! nl2br(e($consignorData['address'])) !!}</div>
                                <div class="party-lines"><span class="label">Phone No.:</span> {{ $consignorPhone }}</div>
                                <div class="party-lines"><span class="label">GST No.:</span> {{ $consignorGstin }}</div>
                            </td>
                            <td class="party-cell">
                                <span class="label">Consignee</span> <span style="font-weight: bold; font-size: 16px; padding-left: 5px;">{{ $consigneeData['name'] }}</span>
                                <div class="party-lines party-details">{!! nl2br(e($consigneeData['address'])) !!}</div>
                                <div class="party-lines"><span class="label">Phone No.:</span> {{ $consigneePhone }}</div>
                                <div class="party-lines"><span class="label">GST No.:</span> {{ $consigneeGstin }}</div>
                            </td>
                        </tr>
                    </table>

                    @php
                        $descLength = strlen($descriptionOfGoods);
                        $descFontSize = '18px';
                        if ($descLength > 120) {
                            $descFontSize = '11px';
                        } elseif ($descLength > 80) {
                            $descFontSize = '13px';
                        } elseif ($descLength > 45) {
                            $descFontSize = '15px';
                        }
                    @endphp
                    <table class="goods">
                        <tr>
                            <td width="50%" class="large" style="font-size: {{ $descFontSize }};"><span class="label">Description of Goods</span><br>{{ $descriptionOfGoods }}</td>
                            <td width="24%"><span class="label">No. of Articles</span><br>{{ $noOfArticles }}</td>
                            <td><span class="label">Packing</span><br>{{ $itemField(['packing']) }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">HSN CODE</span><br>{{ $itemField(['hsn_code']) }}</td>
                            <td><span class="label">Actual Weight</span></td>
                            <td>{{ $itemField(['actual_weight']) }}</td>
                        </tr>
                        <tr>
                            <td rowspan="4" class="delivery-cell">
                                <span class="label">Delivery At.:</span><br>
                                {{ $itemField(['delivery_at']) }}
                                <div class="eway-inline">
                                    <span class="label">E-way Bill No.:</span><br>
                                    {{ $itemField(['e_way_bill_no']) }}
                                </div>
                            </td>
                            <td><span class="label">Charged Weight</span></td>
                            <td>{{ $itemField(['charged_weight']) }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Invoice No. :</span></td>
                            <td>{{ $itemField(['invoice_no']) }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Goods Value</span></td>
                            <td>{{ $itemField(['goods_value']) }}</td>
                        </tr>
                        <tr>
                            <td class="goods-fill"><span class="label">POD Required</span></td>
                            <td>{{ $itemField(['pod_required']) }}</td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    </table>

                    <table class="footer-left">
                        <tr>
                            <td width="50%" style="padding: 0; border-bottom: 0;">
                                <div class="declaration">
                                    <span class="label">DECLARATION :</span> We Have Not Taken Gst Credit As Per The Provisions
                                    Of Convat Credit Rule 2004 Of Only Paid On Inputs Or Capital Goods
                                    Used For Providing Taxable's Service To You And Have Also Availed
                                    The Benefits Of Notification No. 11 & 13/2017 Dated 28th June 2017
                                </div>
                            </td>
                            <td width="50%" rowspan="2" class="consignee-sign">
                                <span class="label">Rubber Stamp and Signature of Consignee</span><br><br><br><br>
                                <span class="label">Phone / Mobile</span><br>
                                &nbsp;
                            </td>
                        </tr>
                        <tr>
                            <td class="agreement">
                                It is taken in to consideration that agrees with<br>all the terms and condition overleaf
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="header-right">
                    <div class="copy-label-box">
                        @php
                            $hasActiveCopy = in_array($copy['key'], ['consignee', 'driver', 'consignor', 'ho', 'file'], true);
                            $isCopy = fn($name) => $copy['key'] === $name;
                            $styleLine = function($name) use ($hasActiveCopy, $isCopy) {
                                if (! $hasActiveCopy) {
                                    return '';
                                }
                                return $isCopy($name) ? 'font-weight: bold; text-decoration: underline;' : 'color: #777; font-size: 11px;';
                            };
                        @endphp
                        <span style="{{ $styleLine('consignee') }}">ORIGINAL WHITE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: CONSIGNEE COPY</span><br>
                        <span style="{{ $styleLine('driver') }}">DUPLICATE GREEN&nbsp;&nbsp;&nbsp;: DRIVER COPY</span><br>
                        <span style="{{ $styleLine('consignor') }}">TRIPLICATE PINK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: CONSIGNOR COPY</span><br>
                        <span style="{{ $styleLine('ho') }}">YELLOW&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: H. O COPY</span><br>
                        <span style="{{ $styleLine('file') }}">DUPLICATE WHITE&nbsp;&nbsp;&nbsp;: FILE COPY</span>
                    </div>
                    <table class="top-detail-table">
                        <tr>
                            <td width="36%"><span class="label">Date :</span> {{ $invoice->formattedInvoiceDate }}</td>
                            <td><span class="label">Docket No.:</span> <span class="docket-no">{{ $docketNumber }}</span></td>
                        </tr>
                        <tr>
                            <td width="36%"><span class="label">Time :</span> {{ $invoiceField(['time']) }}</td>
                            <td><span class="label">From :</span> {{ $invoiceField(['from']) }}</td>
                        </tr>
                        <tr>
                            <td width="36%" class="owner-risk">OWNER'S RISK</td>
                            <td><span class="label">To :</span> {{ $invoiceField(['to']) }}</td>
                        </tr>
                        <tr><td colspan="2"><span class="label">Truck No.:</span> {{ $invoiceField(['truck_no']) }}</td></tr>
                        <tr><td colspan="2" class="tax-line"><span class="label">PAN No.:</span> {{ $panNo }}<br><span class="label">{{ $companyTaxIdentityLabel }} :</span> {{ $companyTaxIdentityValue }}</td></tr>
                    </table>

                    <table class="charges">
                        <tr>
                            <th width="42%">Description of<br>Freight</th>
                            <th width="34%">To Pay/Paid Rs.</th>
                            <th>Mode of<br>Payment</th>
                        </tr>
                        <tr>
                            <td><span class="label">Basic Freight</span></td>
                            <td class="text-right">{{ $basicFreight }}</td>
                            <td class="mode">{!! $modeLabel('TO PAY') !!}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Local Collection</span></td>
                            <td class="text-right">{{ $localCollection }}</td>
                            <td class="mode"></td>
                        </tr>
                        <tr>
                            <td><span class="label">Door Delivery</span></td>
                            <td class="text-right">{{ $doorDelivery }}</td>
                            <td rowspan="3" class="mode">{!! $modeLabel('PAID') !!}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Hamali</span></td>
                            <td class="text-right">{{ $hamali }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Docket Charge</span></td>
                            <td class="text-right">{{ $docketCharge ? $docketCharge.'/-' : '' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Other Charge</span></td>
                            <td class="text-right">{{ $otherCharge }}</td>
                            <td rowspan="4" class="mode">{!! $modeLabel('TO BE BILLED AT') !!}</td>
                        </tr>
                        <tr>
                            <td><span class="label">F.O.V.</span></td>
                            <td class="text-right">{{ $fov }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Sub Total</span></td>
                            <td class="text-right">{!! $moneyText($netAmount) !!}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Net Amount</span></td>
                            <td class="text-right">{!! $moneyText($netAmount) !!}</td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <td class="gst-payable">
                                GST Tax Payable By<br>{{ $gstPayableBy }}
                            </td>
                        </tr>
                        <tr>
                            <td class="for-company">
                                <div>For {{ $companyName }}</div>
                                <div class="sig-container">
                                    @if (file_exists($signaturePath))
                                        <img class="signature-image" src="{{ \App\Space\ImageUtils::toBase64Src($signaturePath) }}" alt="Signature">
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>

</html>
