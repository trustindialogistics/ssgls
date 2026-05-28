<!DOCTYPE html>
<html>

<head>
    <title>@lang('pdf_invoice_label') - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        @page {
            margin: 7px;
            size: 297mm 210mm;
        }

        body {
            color: #2f3548;
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
            border: 1px solid #4b5568;
            padding: 3px 5px;
            vertical-align: top;
        }

        .no-border {
            border: 0;
        }

        .outer {
            border: 1.4px solid #343b4f;
            table-layout: fixed;
        }

        .jurisdiction {
            font-size: 8px;
            line-height: 9px;
            padding: 0 4px 1px;
        }

        .header-left {
            height: 74px;
            padding: 3px 4px;
        }

        .brand-mark {
            border: 0;
            color: #27324a;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: -3px;
            line-height: 36px;
            text-align: center;
            width: 90px;
        }

        .brand-small {
            display: block;
            font-size: 7px;
            letter-spacing: 0;
            line-height: 8px;
            margin-top: 4px;
        }

        .company-title {
            font-size: 24px;
            font-weight: bold;
            line-height: 24px;
        }

        .company-subtitle {
            font-size: 13px;
            font-weight: bold;
            line-height: 14px;
        }

        .company-address {
            font-size: 10px;
            line-height: 12px;
        }

        .mobile {
            font-size: 10px;
            text-align: right;
            white-space: nowrap;
        }

        .branch-box {
            height: 74px;
            line-height: 12px;
        }

        .branch-tax {
            font-size: 15px;
            font-weight: bold;
            line-height: 17px;
            padding-top: 14px;
        }

        .label {
            font-weight: bold;
        }

        .line-area {
            height: 16px;
            line-height: 15px;
        }

        .details-table td {
            height: 16px;
            line-height: 14px;
            padding: 2px 5px;
        }

        .payment-table td,
        .payment-table th {
            font-size: 9px;
            height: 13px;
            padding: 1px 3px;
            text-align: center;
        }

        .items th {
            font-size: 9px;
            font-weight: bold;
            height: 25px;
            line-height: 10px;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
        }

        .items td {
            font-size: 10px;
            padding: 4px 3px;
            text-align: center;
        }

        .items .row-cell {
            height: 20px;
            line-height: 14px;
        }

        .items .blank-fill td {
            border-top: 0;
        }

        .bottom-row td {
            height: 20px;
            line-height: 15px;
        }

        .terms {
            font-size: 8px;
            line-height: 11px;
        }

        .signature {
            font-size: 17px;
            font-weight: bold;
            line-height: 22px;
            text-align: center;
        }

        .signature-space {
            height: 24px;
        }

        .emp-code {
            float: left;
            font-size: 9px;
            font-weight: normal;
            height: 36px;
            line-height: 13px;
            margin-top: 8px;
            width: 58px;
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
                    || $candidates->contains('CUSTOM_ITEM_'.$key)
                    || $candidates->contains('CUSTOM_CUSTOMER_'.$key)
                ) {
                    return $field->defaultAnswer;
                }
            }
        }

        return '';
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

    $companyName = $invoice->company->name ?: 'S S Gujarat Logistics';
    $companyAddressHasEmail = preg_match('/\bE-?mail\b/i', strip_tags((string) $company_address));
    $displayCompanyAddress = preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $company_address);
    $displayCompanyAddress = preg_replace('/^\s*<p[^>]*>\s*<strong>\s*\(A Cost Effective Distribution\)\s*<\/strong>\s*<\/p>\s*/i', '', $displayCompanyAddress);
    $billingBranch = $invoiceField(['billing_branch_name_address', 'billing_branch_address', 'billing_branch']);
    $panNo = $invoiceField(['pan_no', 'pan']) ?: $customerField(['pan_no', 'pan']);
    $gstin = $invoice->customer->tax_id ?: $invoiceField(['gstin', 'gst_no']);
    $partyCode = $invoiceField(['party_code']);
    $branchCode = $invoiceField(['branch_code']);
    $tickBillType = $invoiceField(['tick_bill_type', 'bill_type']);
    $basisOfCharges = $invoiceField(['basis_of_charges', 'basis']);
    $enclosures = $invoiceField(['enclosures']);
    $serviceTaxThrough = $invoiceField(['service_tax_through']) ?: 'CONSIGNOR / CONSIGNEE /';
    $empCode = $invoiceField(['emp_code', 'employee_code']);
    $preparedBy = $invoiceField(['prepared_by']);
    $checkedBy = $invoiceField(['checked_by']);
    $rupeesInWords = $invoiceField(['rupees_in_words', 'amount_in_words']);
    $partyAddress = trim(strip_tags($billing_address)) ? $billing_address : e($invoice->customer->name);
    $blankFillHeight = max(190, 285 - ($invoice->items->count() * 22));
@endphp

    <div class="jurisdiction">Subject to Umbergaon Jurisdiction</div>
    <table class="outer">
        <tr>
            <td colspan="7" class="header-left">
                <table>
                    <tr>
                        <td class="brand-mark">
                            SS
                            <span class="brand-small">GUJARAT LOGISTICS</span>
                        </td>
                        <td class="no-border">
                            <div class="company-title">{{ $companyName }}</div>
                            <div class="company-subtitle">(A Cost Effective Distribution)</div>
                            <div class="company-address">
                                {!! $displayCompanyAddress !!}
                                @if (! $companyAddressHasEmail)
                                    <br>E-mail : {{ $invoiceField(['email']) ?: ($invoice->company->email ?? '') }}
                                @endif
                            </div>
                        </td>
                        <td class="no-border mobile">
                            Mob. {{ $invoiceField(['mobile', 'phone']) ?: ($invoice->company->phone ?? '') }}
                        </td>
                    </tr>
                </table>
            </td>
            <td colspan="5" class="branch-box">
                <span class="label">Billing Br. Name &amp; Address :</span><br>
                {!! nl2br(e($billingBranch)) !!}
                <div class="branch-tax">
                    PAN No.: {{ $panNo }}<br>
                    GSTIN : {{ $gstin }}
                </div>
            </td>
        </tr>

        <tr>
            <td colspan="7" rowspan="3">
                <div class="line-area">
                    <span class="label">Party Name &amp; Address :</span>
                    {!! $partyAddress !!}
                    <span style="float: right;"><span class="label">Party Code :</span> {{ $partyCode }}</span>
                </div>
                <div class="line-area">&nbsp;</div>
                <div class="line-area">&nbsp;</div>
                <div class="line-area"><span class="label">GSTIN :</span> {{ $gstin }}</div>
            </td>
            <td colspan="5">
                <table class="details-table">
                    <tr>
                        <td><span class="label">Bill No.:</span> {{ $invoice->invoice_number }}</td>
                        <td><span class="label">Branch Code :</span> {{ $branchCode }}</td>
                    </tr>
                    <tr>
                        <td><span class="label">Bill Date :</span> {{ $invoice->formattedInvoiceDate }}</td>
                        <td><span class="label">Payment Due Date :</span> {{ $invoice->formattedDueDate }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="5">
                <table class="payment-table">
                    <tr>
                        <th rowspan="2">Tick<br>Bill Type<br>{{ $tickBillType }}</th>
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
            <td colspan="5"><span class="label">Basis of Charges as per G. C. Note or Contract No. :</span> {{ $basisOfCharges }}</td>
        </tr>

        <tr>
            <td colspan="12" style="padding: 0;">
                <table class="items">
                    <tr>
                        <th width="3%">Sl.<br>No.</th>
                        <th width="8%">Consignment / Old Bill<br>Number</th>
                        <th width="8%">Date</th>
                        <th width="11%">Invoice<br>No.</th>
                        <th width="9%">Destination</th>
                        <th width="9%">Vehicle No.</th>
                        <th width="6%">Pkg.</th>
                        <th width="8%">Charged<br>Weight Kgs.</th>
                        <th width="10%">Rate</th>
                        <th width="8%">Other Charge</th>
                        <th width="7%">DD Charge</th>
                        <th width="13%">Amount</th>
                    </tr>
                    @foreach ($invoice->items as $index => $item)
                        <tr>
                            <td class="row-cell">{{ $index + 1 }}</td>
                            <td class="row-cell">{{ $itemField($item, ['consignment_number', 'old_bill_number', 'consignment_old_bill_number']) }}</td>
                            <td class="row-cell">{{ $itemField($item, ['consignment_date', 'old_bill_date', 'date']) }}</td>
                            <td class="row-cell">{{ $itemField($item, ['invoice_no', 'invoice_number']) ?: $invoice->invoice_number }}</td>
                            <td class="row-cell">{{ $itemField($item, ['destination']) }}</td>
                            <td class="row-cell">{{ $itemField($item, ['vehicle_no', 'vehicle_number']) }}</td>
                            <td class="row-cell">{{ $itemField($item, ['pkg', 'package', 'packages']) ?: $item->quantity }}</td>
                            <td class="row-cell">{{ $itemField($item, ['charged_weight_kgs', 'charged_weight', 'weight']) }}</td>
                            <td class="row-cell">{!! $itemField($item, ['rate']) ? e($itemField($item, ['rate'])) : format_money_pdf($item->price, $invoice->customer->currency) !!}</td>
                            <td class="row-cell">{{ $itemField($item, ['other_charge']) }}</td>
                            <td class="row-cell">{{ $itemField($item, ['dd_charge']) }}</td>
                            <td class="row-cell">{!! format_money_pdf($item->total, $invoice->customer->currency) !!}</td>
                        </tr>
                    @endforeach
                    <tr class="blank-fill">
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                        <td style="height: {{ $blankFillHeight }}px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="bottom-row">
            <td colspan="8"><span class="label">Rupees in words :</span> {{ $rupeesInWords }}</td>
            <td colspan="2" class="text-center label">GRAND TOTAL</td>
            <td colspan="2" class="text-right">{!! format_money_pdf($invoice->total, $invoice->customer->currency) !!}</td>
        </tr>
        <tr class="bottom-row">
            <td colspan="4"><span class="label">Enclosures :</span> {{ $enclosures }}</td>
            <td colspan="4" class="text-center"><span class="label">Service Tax Through :</span> {{ $serviceTaxThrough }}</td>
            <td colspan="4" rowspan="3" class="signature">
                <div class="emp-code"><span class="label">EMP Code</span><br>{{ $empCode }}</div>
                For {{ $companyName }}
                <div class="signature-space"></div>
                Signature
            </td>
        </tr>
        <tr>
            <td colspan="4" rowspan="2" class="terms">
                1) Payment should be made by payee A/c Cheque / D.D. favour of {{ $companyName }}<br>
                2) Interest @ 10% per annum will be charged if bill not paid within 7 days from date of bill
            </td>
            <td colspan="2" rowspan="2" class="text-right">
                <br><br><span class="label">Prepared by :</span> {{ $preparedBy }}
            </td>
            <td colspan="2" rowspan="2" class="text-center">
                <br><br><span class="label">Checked by :</span> {{ $checkedBy }}
            </td>
        </tr>
        <tr>
        </tr>
    </table>
</body>

</html>
