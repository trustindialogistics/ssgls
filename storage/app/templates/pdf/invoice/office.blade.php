<!DOCTYPE html>
<html>

<head>
    <title>@lang('pdf_invoice_label') - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @php
        use Illuminate\Support\Str;

        $currency = $invoice->customer->currency ?? $invoice->currency;

        $getCustomField = function ($model, string $modelType, string $slug, string $fallback = ''): string {
            $customSlug = 'CUSTOM_'.$modelType.'_'.Str::upper(Str::slug($slug, '_'));

            return (string) ($model->getCustomFieldValueBySlug($slug) ?? $model->getCustomFieldValueBySlug($customSlug) ?? $fallback);
        };
        $inv = function (string $slug, string $fallback = '') use ($getCustomField, $invoice): string {
            return $getCustomField($invoice, 'INVOICE', $slug, $fallback);
        };
        $cust = function (string $slug, string $fallback = '') use ($getCustomField, $invoice): string {
            return $getCustomField($invoice->customer, 'CUSTOMER', $slug, $fallback);
        };
        $itemField = function ($item, string $slug, string $fallback = '') use ($getCustomField): string {
            return $getCustomField($item, 'ITEM', $slug, $fallback);
        };
        $money = function ($amount) use ($currency): string {
            return trim(strip_tags(format_money_pdf((int) $amount, $currency)));
        };

        $companyName = $invoice->company?->name ?? '';
        $companyPan = 'BHLPS2943H';
        $companyGstin = '24BHLPS2943H1Z3';
        $customerGstin = $invoice->customer?->tax_id ?: $cust('gstin');
        $companyPhone = $invoice->company?->address?->phone;

        // Shared view variables come from Invoice::getPDFData(). Guard against null/false.
        $company_address = $company_address ?: '';
        $billing_address = $billing_address ?: '';
        $notes = $notes ?: '';
    @endphp

    <style type="text/css">
        @page {
            margin: 18pt 20pt;
            size: letter;
        }

        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #111827;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .muted {
            color: #374151;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .box {
            border: 1px solid #111827;
        }

        .cell {
            border: 1px solid #111827;
            vertical-align: top;
            padding: 4pt 5pt;
        }

        .cell-tight {
            padding: 3pt 4pt;
        }

        .label {
            font-size: 8px;
            color: #111827;
            font-weight: 600;
        }

        .value {
            font-size: 9px;
            margin-top: 2pt;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .no-border {
            border: 0;
        }

        .company-header td {
            border: 0;
            padding: 0;
        }

        .company-logo-cell {
            text-align: center;
            width: 62pt;
        }

        .company-logo {
            max-height: 44pt;
            max-width: 56pt;
        }

        .company-fallback-logo {
            color: #111827;
            font-size: 18px;
            font-weight: 700;
            line-height: 18px;
        }

        .company-contact {
            font-size: 8px;
            line-height: 11px;
            margin-top: 3pt;
        }

        .items th {
            font-size: 8px;
            font-weight: 700;
            background: #f3f4f6;
            padding: 4pt 4pt;
            border: 1px solid #111827;
        }

        .items td {
            border: 1px solid #111827;
            padding: 3pt 4pt;
            vertical-align: top;
        }

        .footer-note {
            font-size: 8px;
            color: #111827;
        }

        .signature {
            height: 44pt;
        }
    </style>
</head>

<body>
    <table class="box">
        <tr>
            <td class="cell" style="width: 62%;">
                <table class="company-header">
                    <tr>
                        <td class="company-logo-cell">
                            @if ($logo)
                                <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                            @else
                                <div class="company-fallback-logo">{{ $companyName }}</div>
                            @endif
                        </td>
                        <td style="padding-left: 6pt;">
                            <div class="title">{{ $companyName }}</div>
                            <div class="muted" style="margin-top: 2pt;">{!! $company_address !!}</div>
                            @if ($companyPhone)
                                <div class="company-contact"><span class="label">Phone / Mobile :</span> {{ $companyPhone }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="cell" style="width: 38%;">
                <div class="label">Billing Br. Name &amp; Address :</div>
                <div class="value">{!! nl2br(e($inv('billing_branch_name_address'))) !!}</div>
                <div style="margin-top: 6pt;">
                    <div class="label">PAN No. :</div>
                    <div class="value">{{ $inv('company_pan', $companyPan) }}</div>
                </div>
                <div style="margin-top: 4pt;">
                    <div class="label">GSTIN :</div>
                    <div class="value">{{ $inv('company_gstin', $companyGstin) }}</div>
                </div>
            </td>
        </tr>

        <tr>
            <td class="cell" colspan="2">
                <table>
                    <tr>
                        <td class="no-border" style="width: 58%; padding-right: 10pt;">
                            <div class="label">Party Name &amp; Address :</div>
                            <div class="value">{!! $billing_address !!}</div>
                        </td>
                        <td class="no-border" style="width: 42%;">
                            <table>
                                <tr>
                                    <td class="cell cell-tight" style="width: 40%;">
                                        <div class="label">Party Code :</div>
                                        <div class="value">{{ $inv('party_code') }}</div>
                                    </td>
                                    <td class="cell cell-tight" style="width: 60%;">
                                        <div class="label">Bill No. :</div>
                                        <div class="value">{{ $invoice->invoice_number }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="cell cell-tight">
                                        <div class="label">Branch Code :</div>
                                        <div class="value">{{ $inv('branch_code') }}</div>
                                    </td>
                                    <td class="cell cell-tight">
                                        <div class="label">Bill Date :</div>
                                        <div class="value">{{ $invoice->formattedInvoiceDate }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="cell cell-tight" colspan="2">
                                        <div class="label">Payment Due Date :</div>
                                        <div class="value">{{ $inv('payment_due_date', $invoice->formattedDueDate) }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="no-border" style="padding-top: 6pt;">
                            <div class="label">GSTIN :</div>
                            <div class="value">{{ $customerGstin }}</div>
                        </td>
                        <td class="no-border" style="padding-top: 6pt;">
                            <table>
                                <tr>
                                    <td class="cell cell-tight center" style="width: 14%;">
                                        <div class="label">Tick</div>
                                        <div class="value">{{ $inv('payment_tick') }}</div>
                                    </td>
                                    <td class="cell cell-tight center" style="width: 14%;">
                                        <div class="label">Cash</div>
                                        <div class="value">{{ $inv('payment_cash') }}</div>
                                    </td>
                                    <td class="cell cell-tight center" style="width: 18%;">
                                        <div class="label">Cheque No.</div>
                                        <div class="value">{{ $inv('payment_cheque_no') }}</div>
                                    </td>
                                    <td class="cell cell-tight center" style="width: 14%;">
                                        <div class="label">Date</div>
                                        <div class="value">{{ $inv('payment_date') }}</div>
                                    </td>
                                    <td class="cell cell-tight center" style="width: 20%;">
                                        <div class="label">Bank</div>
                                        <div class="value">{{ $inv('payment_bank') }}</div>
                                    </td>
                                    <td class="cell cell-tight center" style="width: 20%;">
                                        <div class="label">Others</div>
                                        <div class="value">{{ $inv('payment_others') }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="cell cell-tight" colspan="6">
                                        <div class="label">Bill Type :</div>
                                        <div class="value">{{ $inv('bill_type') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="no-border" colspan="2" style="padding-top: 6pt;">
                            <div class="label">Basis of Charges as per G. C. Note or Contract No. :</div>
                            <div class="value">{{ $inv('basis_of_charges') }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="cell" colspan="2" style="padding: 0;">
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width: 4%;">Sl. No.</th>
                            <th style="width: 12%;">Consignment / Old Bill Number</th>
                            <th style="width: 9%;">Date</th>
                            <th style="width: 10%;">Invoice No.</th>
                            <th style="width: 12%;">Destination</th>
                            <th style="width: 10%;">Vehicle No.</th>
                            <th style="width: 6%;">Pkg.</th>
                            <th style="width: 10%;">Charged Weight Kgs.</th>
                            <th style="width: 8%;">Rate</th>
                            <th style="width: 9%;">Other Charge</th>
                            <th style="width: 8%;">DD Charge</th>
                            <th style="width: 12%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $index => $item)
                            <tr>
                                <td class="center">{{ $index + 1 }}</td>
                                <td>{{ $item->name }}</td>
                                <td class="center">{{ $invoice->formattedInvoiceDate }}</td>
                                <td class="center">{{ $invoice->invoice_number }}</td>
                                <td>{{ $itemField($item, 'destination', trim(strip_tags($item->description ?: ''))) }}</td>
                                <td class="center">{{ $itemField($item, 'vehicle_no') }}</td>
                                <td class="right">{{ (int) $item->quantity }}</td>
                                <td class="right">{{ $itemField($item, 'charged_weight') }}</td>
                                <td class="right">{{ $itemField($item, 'rate', $money($item->price)) }}</td>
                                <td class="right">{{ $itemField($item, 'other_charge') }}</td>
                                <td class="right">{{ $itemField($item, 'dd_charge') }}</td>
                                <td class="right">{{ $money($item->total) }}</td>
                            </tr>
                        @endforeach
                        @for ($i = $invoice->items->count(); $i < 10; $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </td>
        </tr>

        <tr>
            <td class="cell" colspan="2">
                <table>
                    <tr>
                        <td class="no-border" style="width: 70%;">
                            <div class="label">Rupees in words :</div>
                            <div class="value">{{ $inv('amount_in_words', $money($invoice->total)) }}</div>
                        </td>
                        <td class="cell right" style="width: 30%;">
                            <div class="label">GRAND TOTAL</div>
                            <div class="value" style="font-weight: 700; font-size: 11px;">{{ $money($invoice->total) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="no-border" style="padding-top: 6pt;">
                            <div class="label">Enclosures :</div>
                            <div class="value">{{ $inv('enclosures', trim(strip_tags($notes ?: ''))) }}</div>
                        </td>
                        <td class="no-border" style="padding-top: 6pt;">
                            <div class="label center">For {{ $companyName }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="no-border footer-note" style="padding-top: 6pt;">
                            <div>1) Payment should be made by payee A/c Cheque / D.D. Favour of "{{ $companyName }}"</div>
                            <div>2) Interest @ 10% per annum will be charged if bill not paid that? days from date of bill</div>
                        </td>
                        <td class="no-border" style="padding-top: 6pt;">
                            <table>
                                <tr>
                                    <td class="cell center" style="width: 30%;">
                                        <div class="label">EMP Code</div>
                                        <div class="value">{{ $inv('emp_code') }}</div>
                                    </td>
                                    <td class="cell signature center" style="width: 70%;">
                                        <div class="label">Signature</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="no-border center" style="padding-top: 6pt;">
                                        <div class="label">Prepared by :</div>
                                        <div class="value">{{ $inv('prepared_by') }}</div>
                                    </td>
                                    <td class="no-border center" style="padding-top: 6pt;">
                                        <div class="label">Checked by :</div>
                                        <div class="value">{{ $inv('checked_by') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
