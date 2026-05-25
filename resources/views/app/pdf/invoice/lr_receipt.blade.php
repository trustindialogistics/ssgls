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
            color: #1f273d;
            font-family: "DejaVu Sans";
            font-size: 9px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #252b3d;
            padding: 2px 5px;
            vertical-align: top;
        }

        .wrapper {
            border: 1.5px solid #252b3d;
            page-break-inside: avoid;
            width: 100%;
        }

        .no-border {
            border: 0;
        }

        .jurisdiction {
            border-bottom: 1px solid #252b3d;
            font-size: 8px;
            line-height: 10px;
            text-align: center;
        }

        .header-left {
            border-right: 1.5px solid #252b3d !important;
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
            padding: 4px 7px !important;
            position: relative;
            width: 100%;
        }

        .company-logo {
            max-height: 68px;
            max-width: 92px;
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
            color: #26304a;
            font-size: 24px;
            font-weight: bold;
            line-height: 25px;
            margin-top: 2px;
        }

        .company-tagline {
            font-size: 10px;
            font-weight: bold;
            line-height: 12px;
        }

        .company-address {
            font-size: 9px;
            line-height: 11px;
            margin-top: 2px;
        }

        .header-contact {
            font-size: 9px;
            line-height: 12px;
            position: absolute;
            right: 7px;
            text-align: right;
            top: 6px;
            white-space: nowrap;
        }

        .brand-block {
            height: auto;
        }

        .brand-block td {
            border: 0;
            padding: 2px 6px;
            vertical-align: top;
        }

        .copy-table td {
            border: 0;
            font-size: 9px;
            font-weight: bold;
            line-height: 12px;
            padding: 1px 7px;
        }

        .top-detail-table td {
            height: 18px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .top-detail-table .tax-line {
            height: 34px;
        }

        .party-table td {
            border-bottom: 0;
        }

        .party-cell {
            height: 50px;
            padding: 4px 6px;
            width: 50%;
        }

        .party-lines {
            font-size: 7.5px;
            line-height: 8.5px;
            margin-top: 1px;
            overflow: hidden;
        }

        .party-details {
            height: 34px;
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
            font-size: 17px;
            font-weight: bold;
            letter-spacing: 3px;
            text-align: center;
        }

        .owner-risk {
            font-size: 11px;
            font-weight: bold;
        }

        .tax-line {
            font-size: 11px;
            font-weight: bold;
            line-height: 13px;
        }

        .goods td {
            height: 22px;
            line-height: 14px;
        }

        .goods .large {
            height: 30px;
        }

        .left-panel {
            padding: 0;
            vertical-align: top;
            width: 61%;
        }

        .freight-panel {
            padding: 0;
            vertical-align: top;
            width: 39%;
        }

        .charges th {
            font-size: 9px;
            height: 20px;
            line-height: 11px;
            text-align: center;
            vertical-align: middle;
        }

        .charges td {
            height: 20.5px;
            line-height: 14px;
        }

        .mode {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .copy-label-box {
            font-size: 18px;
            font-weight: bold;
            height: 44px;
            line-height: 44px;
            text-align: center;
        }

        .goods-fill {
            height: 44px;
        }

        .declaration {
            font-size: 7.5px;
            line-height: 9px;
            height: 58px;
        }

        .agreement {
            font-size: 8px;
            font-weight: bold;
            line-height: 11px;
            text-align: center;
        }

        .consignee-sign {
            height: 70px;
            line-height: 13px;
        }

        .gst-payable {
            font-size: 10px;
            font-weight: bold;
            height: 70px;
            line-height: 12px;
            text-align: center;
            vertical-align: middle;
        }

        .for-company {
            border-top: 1.5px solid #252b3d !important;
            font-size: 13px;
            font-weight: bold;
            height: 36px;
            line-height: 18px;
            padding-top: 5px;
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

    $companyName = $invoice->company?->name ?: 'S S Gujarat Logistics';
    $companyAddress = trim(strip_tags($company_address))
        ? $company_address
        : '1953, Ground Floor, Mehsana Steel Compound, Umbergaon,<br>Dist. Valsad, Gujarat - 396171';
    $companyPhone = $invoice->company?->address?->phone;
    $mobile = $companyPhone ?: ($invoiceField(['mobile', 'phone']) ?: '6355071130');
    $email = $invoiceField(['email']) ?: 'ssglogistic2021@gmail.com';
    $displayCompanyAddress = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $companyAddress);
    $displayCompanyAddress = preg_replace('/<p[^>]*>\s*(?:<strong>)?\s*\(?A Cost Effective Distribution\)?\s*(?:<\/strong>)?\s*<\/p>/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/<br\s*\/?>\s*\(?A Cost Effective Distribution\)?/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/\(?A Cost Effective Distribution\)?/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*E-?mail\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*Mob(?:ile)?\.?\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $panNo = 'BHLPS2943H';
    $gstin = '24BHLPS2943H1Z3';

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
    $gstPayableBy = $invoiceField(['gst_tax_payable_by']) ?: 'Consignor / Consignee';
    $consigneeName = $partyDetails($invoice->customer, $invoiceField(['consignee']));
    $consigneePhone = $invoice->customer->phone ?: $invoiceField(['consignee_phone_no']);
    $consigneeGstin = $invoice->customer->tax_id ?: $invoiceField(['consignee_gst_no']);
    $consignorName = $invoiceField(['consignor']);
    $consignorPhone = $invoiceField(['consignor_phone_no']);
    $consignorGstin = $invoiceField(['consignor_gst_no']);
    $docketNumber = preg_replace('/^INV/i', 'DOC', $invoice->invoice_number);
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
        <div class="jurisdiction">Subject to Umbergaon Jurisdiction</div>

        <table>
            <tr>
                <td class="header-left">
                    <table class="brand-block">
                        <tr>
                            <td class="company-panel">
                                <div class="header-contact">
                                    Mob. {{ $mobile }}<br>
                                    E-mail : {{ $email }}
                                </div>
                                @if ($logo)
                                    <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-mark">
                                        SS
                                        <span class="brand-small">GUJARAT LOGISTICS</span>
                                    </div>
                                @endif
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">(A Cost Effective Distribution)</div>
                                <div class="company-address">{!! $displayCompanyAddress !!}</div>
                            </td>
                        </tr>
                    </table>
                    <table class="party-table">
                        <tr>
                            <td class="party-cell">
                                <span class="label">Consignor</span>
                                <div class="party-lines party-details">{!! nl2br(e($fitPartyText($consignorName))) !!}</div>
                                <div class="party-lines"><span class="label">Phone No.:</span> {{ $consignorPhone }}</div>
                                <div class="party-lines"><span class="label">GST No.:</span> {{ $consignorGstin }}</div>
                            </td>
                            <td class="party-cell">
                                <span class="label">Consignee</span>
                                <div class="party-lines party-details">{!! nl2br(e($fitPartyText($consigneeName))) !!}</div>
                                <div class="party-lines"><span class="label">Phone No.:</span> {{ $consigneePhone }}</div>
                                <div class="party-lines"><span class="label">GST No.:</span> {{ $consigneeGstin }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="header-right">
                    <div class="copy-label-box">{{ $copyLabel ?? '' }}</div>
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
                        <tr><td colspan="2" class="tax-line"><span class="label">PAN No.:</span> {{ $panNo }}<br><span class="label">GSTIN :</span> {{ $gstin }}</td></tr>
                    </table>
                    <table class="copy-table">
                        <tr><td>ORIGINAL WHITE</td><td>: CONSIGNEE COPY</td></tr>
                        <tr><td>DUPLICATE GREEN</td><td>: DRIVER COPY</td></tr>
                        <tr><td>TRIPALICATE PINK</td><td>: CONSIGNOR COPY</td></tr>
                        <tr><td>YELLOW</td><td>: H. O. COPY</td></tr>
                        <tr><td>DUPLICATE WHITE</td><td>: FILE COPY</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td class="left-panel">
                    <table class="goods">
                        <tr>
                            <td width="50%" class="large"><span class="label">Description of Goods</span><br>{{ $descriptionOfGoods }}</td>
                            <td width="24%"><span class="label">No. of Articles</span><br>{{ $noOfArticles }}</td>
                            <td><span class="label">Packing</span><br>{{ $itemField(['packing']) }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">HSN CODE</span><br>{{ $itemField(['hsn_code']) }}</td>
                            <td colspan="2" style="padding: 0;">
                                <table>
                                    <tr>
                                        <td width="50%"><span class="label">Actual Weight</span><br>{{ $itemField(['actual_weight']) }}</td>
                                        <td><span class="label">Charged Weight</span><br>{{ $itemField(['charged_weight']) }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td rowspan="4"><span class="label">Delivery At.:</span><br>{{ $itemField(['delivery_at']) }}</td>
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
                            <td class="goods-fill"><span class="label">POD Required.</span></td>
                            <td>{{ $itemField(['pod_required']) }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">E-way Bill No.:</span><br>{{ $itemField(['e_way_bill_no']) }}</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td class="freight-panel">
                    <table class="charges">
                        <tr>
                            <th colspan="3">Description of Freight</th>
                        </tr>
                        <tr>
                            <th width="42%">&nbsp;</th>
                            <th width="34%">To Pay/Paid Rs.</th>
                            <th>Mode of<br>Payment</th>
                        </tr>
                        <tr>
                            <td><span class="label">Basic Freight</span></td>
                            <td class="text-right">{{ $basicFreight }}</td>
                            <td rowspan="3" class="mode">{{ $modeOfPayment === 'TO PAY' ? 'TO PAY' : '' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Local Collection</span></td>
                            <td class="text-right">{{ $localCollection }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Door Delivery</span></td>
                            <td class="text-right">{{ $doorDelivery }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Hamali</span></td>
                            <td class="text-right">{{ $hamali }}</td>
                            <td rowspan="2" class="mode">{{ $modeOfPayment === 'PAID' ? 'PAID' : '' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Docket Charge</span></td>
                            <td class="text-right">{{ $docketCharge ? $docketCharge.'/-' : '' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Other Charge</span></td>
                            <td class="text-right">{{ $otherCharge }}</td>
                            <td rowspan="4" class="mode">{{ $modeOfPayment === 'TO BE BILLED AT' ? 'TO BE BILLED AT' : '' }}</td>
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
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td width="61%" style="padding: 0;">
                    <table>
                        <tr>
                            <td width="45%" style="padding: 0;">
                                <div class="declaration">
                                    <span class="label">DECLARATION :</span> We Have Not Taken Gst Credit As Per The Provisions
                                    Of Convat Credit Rule 2004 Of Only Paid On Inputs Or Capital Goods
                                    Used For Providing Taxable's Service To You And Have Also Availed
                                    The Benefits Of Notification No. 11 &amp; 13/2017 Dated 28th June 2017
                                </div>
                                <div class="agreement">It is taken in to consideration that agrees with<br>all the terms and condition overleaf</div>
                            </td>
                            <td width="55%" class="consignee-sign">
                                <span class="label">Rubber Stamp and Signature of Consignee</span><br><br><br>
                                <span class="label">Phone / Mobile</span><br>
                                {{ $consigneePhone }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="39%" style="padding: 0;">
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
