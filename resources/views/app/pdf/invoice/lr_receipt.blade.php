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
            font-size: 8.2px;
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
            border: 2px solid #000;
            page-break-inside: avoid;
            width: 100%;
        }

        .no-border {
            border: 0;
        }

        .jurisdiction {
            font-size: 7px;
            left: 190px;
            line-height: 9px;
            position: absolute;
            text-align: center;
            text-decoration: underline;
            top: 6px;
            width: 190px;
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

        .company-panel {
            border: 0;
            height: 132px;
            padding: 2px 7px !important;
            position: relative;
            width: 100%;
        }

        .company-logo {
            max-height: 88px;
            max-width: 118px;
        }

        .brand-mark {
            color: #27324a;
            font-size: 48px;
            font-weight: bold;
            letter-spacing: -5px;
            line-height: 42px;
            text-align: left;
            width: 92px;
        }

        .brand-small {
            display: block;
            font-size: 6px;
            letter-spacing: 0;
            line-height: 7px;
            margin-top: 2px;
        }

        .company-name {
            color: #111;
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 28px;
            font-weight: bold;
            line-height: 28px;
            margin-top: 3px;
        }

        .company-tagline {
            font-size: 10.5px;
            font-weight: bold;
            line-height: 11px;
        }

        .company-address {
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 8px;
            line-height: 9.2px;
            margin-top: 3px;
        }

        .header-contact {
            font-size: 13px;
            line-height: 14px;
            position: absolute;
            right: 7px;
            text-align: right;
            top: 2px;
            white-space: nowrap;
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
            height: 20px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .top-detail-table .tax-line {
            height: 24px;
        }

        .party-table {
            border-top: 2.5px solid #000 !important;
        }

        .party-table td {
            border-bottom: 0;
            border-top: 0;
        }

        .party-cell {
            height: 132px;
            padding: 4px 6px;
            width: 50%;
        }

        .party-lines {
            border-bottom: 1.5px solid #000;
            font-size: 8.5px;
            height: 23px;
            line-height: 21px;
            margin-top: 0;
            overflow: hidden;
        }

        .party-details {
            height: 59px;
            line-height: 13px;
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
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0;
            text-align: left;
        }

        .owner-risk {
            font-size: 11px;
            font-weight: bold;
        }

        .tax-line {
            font-size: 10.5px;
            font-weight: bold;
            line-height: 13px;
            overflow-wrap: anywhere;
        }

        .goods {
            border-top: 2.5px solid #000;
        }

        .goods td {
            font-size: 8.8px;
            height: 20px;
            line-height: 11px;
        }

        .goods .large {
            height: 36px;
        }

        .delivery-cell {
            font-size: 9px;
            line-height: 12px;
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

        .freight-panel {
            padding: 0;
            vertical-align: top;
            width: 100%;
        }

        .charges th {
            font-size: 8px;
            font-weight: bold;
            height: 25px;
            line-height: 10px;
            text-align: center;
            vertical-align: middle;
        }

        .charges td {
            height: 17px;
            line-height: 12px;
        }

        .mode {
            font-size: 12px;
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
            font-size: 12px;
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
            height: 88px;
        }

        .declaration {
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: 8.2px;
            line-height: 8.9px;
            height: 51px;
            overflow: hidden;
            padding: 3px 5px;
        }

        .agreement {
            border-top: 1.5px solid #000;
            font-size: 9.4px;
            font-weight: bold;
            height: 36px;
            line-height: 11px;
            padding-top: 7px;
            text-align: center;
        }

        .consignee-sign {
            height: 88px;
            line-height: 13px;
            padding: 4px 6px;
        }

        .gst-payable {
            font-size: 10px;
            font-weight: bold;
            height: 39px;
            line-height: 12px;
            padding-top: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .for-company {
            border-bottom: 0 !important;
            border-top: 1.5px solid #000 !important;
            font-size: 13px;
            font-weight: bold;
            height: 49px;
            line-height: 18px;
            padding-top: 12px;
            text-align: center;
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
    $companyAddress = trim(strip_tags($company_address)) ? $company_address : '';
    $companyPhone = $invoice->company?->address?->phone;
    $companyEmail = \App\Models\CompanySetting::getSetting('notification_email', $invoice->company_id);
    $mobile = $companyPhone ?: ($invoiceField(['mobile', 'phone']) ?: '');
    $email = $invoiceField(['email']) ?: ($companyEmail ?: '');
    $displayCompanyAddress = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $companyAddress);
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
    $gstPayableBy = $invoiceField(['gst_tax_payable_by']) ?: 'Consignor / Consignee';
    $consigneeName = $invoiceField(['consignee']);
    $consigneePhone = $invoice->customer->phone ?: $invoiceField(['consignee_phone_no']);
    $consigneeGstin = $invoice->customer->tax_id ?: $invoiceField(['consignee_gst_no']);
    $consignorName = $invoiceField(['consignor']);
    $consignorPhone = $invoiceField(['consignor_phone_no']);
    $consignorGstin = $invoiceField(['consignor_gst_no']);
    $docketNumber = $invoice->invoice_number;
    $descriptionOfGoods = trim((string) $itemField(['description_of_goods']));
    $noOfArticles = trim((string) $itemField(['no_of_articles']));

    if (preg_match('/^LR Receipt\s+\d+$/i', $descriptionOfGoods)) {
        $descriptionOfGoods = '';
    }

    if ($noOfArticles === '1' && $descriptionOfGoods === '') {
        $noOfArticles = '';
    }
@endphp

    <div class="wrapper">
        <table>
            <tr>
                <td class="header-left">
                    <table class="brand-block">
                        <tr>
                            <td class="company-panel">
                                <div class="jurisdiction">Subject to Vapi Jurisdiction</div>
                                <div class="header-contact">
                                    Mob. {{ $mobile }}<br>
                                    E-mail : {{ $email }}
                                </div>
                                @if ($logo)
                                    <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-mark">
                                        {{ $companyInitials }}
                                    </div>
                                @endif
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">{{ $companyTagline }}</div>
                                <div class="company-address">{!! $displayCompanyAddress !!}</div>
                            </td>
                        </tr>
                    </table>

                    <table class="party-table">
                        <tr>
                            <td class="party-cell">
                                <span class="label">Consignor</span> ______________________________
                                <div class="party-lines party-details">{!! nl2br(e($fitPartyText($consignorName))) !!}</div>
                                <div class="party-lines"><span class="label">GST No.:</span> {{ $consignorGstin }}</div>
                            </td>
                            <td class="party-cell">
                                <span class="label">Consignee</span> ______________________________
                                <div class="party-lines party-details">{!! nl2br(e($fitPartyText($consigneeName))) !!}</div>
                                <div class="party-lines"><span class="label">GST No.:</span> {{ $consigneeGstin }}</div>
                            </td>
                        </tr>
                    </table>

                    <table class="goods">
                        <tr>
                            <td width="50%" class="large"><span class="label">Description of Goods</span><br>{{ $descriptionOfGoods }}</td>
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
                            <td width="50%" style="padding: 0;">
                                <div class="declaration">
                                    <span class="label">DECLARATION :</span> We Have Not Taken Gst Credit As Per The Provisions
                                    Of Convat Credit Rule 2004 Of Only Paid On Inputs Or Capital Goods
                                    Used For Providing Taxable's Service To You And Have Also Availed
                                    The Benefits Of Notification No. 11 & 13/2017 Dated 28th June 2017
                                </div>
                                <div class="agreement">It is taken in to consideration that agrees with<br>all the terms and condition overleaf</div>
                            </td>
                            <td width="50%" class="consignee-sign">
                                <span class="label">Rubber Stamp and Signature of Consignee</span><br><br><br><br>
                                <span class="label">Phone / Mobile</span><br>
                                &nbsp;
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="header-right">
                    <div class="copy-label-box">
                        ORIGINAL WHITE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: CONSIGNEE COPY<br>
                        DUPLICATE GREEN&nbsp;&nbsp;&nbsp;: DRIVER COPY<br>
                        TRIPLICATE PINK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: CONSIGNOR COPY<br>
                        YELLOW&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: H. O COPY<br>
                        DUPLICATE WHITE&nbsp;&nbsp;&nbsp;: FILE COPY
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
                            <td class="for-company"><div class="company-separator"></div>For {{ $companyName }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
