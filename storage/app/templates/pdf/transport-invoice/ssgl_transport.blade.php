<!DOCTYPE html>
<html>

<head>
    <title>Transport Invoice - {{ $transportInvoice->lr_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @php
        $companyName = $company?->name ?? '';
        $customer = $transportInvoice->customer;
        $rows = $transportInvoice->rows ?? collect();

        $grandTotal = $rows->sum(function ($row) {
            return (float) ($row->amount ?? 0);
        });

        $panNo = 'NBKPS0084L';
        $gstin = '24NBKPS0084L1ZZ';
    @endphp

    <style type="text/css">
        @page {
            size: A4;
            margin: 5mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        .outer {
            border: 1px solid #111;
        }

        .cell {
            border: 1px solid #111;
            padding: 2mm 2mm;
            vertical-align: top;
        }

        .tight {
            padding: 1.5mm 1.5mm;
        }

        .label {
            font-size: 9px;
            font-weight: 700;
        }

        .value {
            margin-top: 1mm;
            font-size: 10px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .h-xxs {
            height: 6mm;
        }

        .h-xs {
            height: 9mm;
        }

        .h-sm {
            height: 14mm;
        }

        .h-md {
            height: 22mm;
        }

        .items th {
            border: 1px solid #111;
            font-size: 9px;
            font-weight: 700;
            padding: 1.5mm 1mm;
            text-align: center;
        }

        .items td {
            border: 1px solid #111;
            padding: 1.5mm 1mm;
            font-size: 9px;
            vertical-align: top;
        }

        .items .row-h {
            height: 10mm;
        }
    </style>
</head>

<body>
    <div class="page">
        <table class="outer">
            <tr>
                <td class="cell" style="width: 62%;">
                    <table style="width:100%;">
                        <tr>
                            <td style="width: 18mm;" class="tight">
                                @if ($logo)
                                    <img src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" style="width: 16mm; height: 16mm;" alt="Logo">
                                @endif
                            </td>
                            <td class="tight">
                                <div style="font-size: 18px; font-weight: 800;">{{ $companyName }}</div>
                                <div style="font-size: 9px; margin-top: 1mm;">{!! $company_address !!}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="cell" style="width: 38%;">
                    <div class="label">Billing Br. Name &amp; Address :</div>
                    <div class="value">{!! $company_address !!}</div>
                    <div style="margin-top: 2mm;">
                        <span class="label">PAN No. :</span>
                        <span class="value">{{ $panNo }}</span>
                    </div>
                    <div style="margin-top: 1mm;">
                        <span class="label">GSTIN :</span>
                        <span class="value">{{ $gstin }}</span>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="cell" style="width: 62%;">
                    <div class="label">Party Name &amp; Address :</div>
                    <div class="value">{!! $billing_address !!}</div>
                    <div style="margin-top: 2mm;">
                        <span class="label">GSTIN :</span>
                        <span class="value">{{ $customer?->tax_id }}</span>
                    </div>
                </td>
                <td class="cell" style="width: 38%; padding: 0;">
                    <table style="width:100%;">
                        <tr>
                            <td class="cell tight" style="width: 55%;">
                                <div class="label">Bill No. :</div>
                                <div class="value">{{ $transportInvoice->lr_number }}</div>
                            </td>
                            <td class="cell tight" style="width: 45%;">
                                <div class="label">Branch Code :</div>
                                <div class="value">{{ $transportInvoice->branch_code }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="cell tight">
                                <div class="label">Bill Date :</div>
                                <div class="value">{{ $transportInvoice->formattedInvoiceDate }}</div>
                            </td>
                            <td class="cell tight">
                                <div class="label">Payment Due Date :</div>
                                <div class="value">{{ $transportInvoice->formattedDueDate }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="cell tight" colspan="2">
                                <table>
                                    <tr>
                                        <td class="cell tight center" style="width: 16%;">Tick<br>Bill Type</td>
                                        <td class="cell tight center" style="width: 16%;">Cash</td>
                                        <td class="cell tight center" style="width: 20%;">Cheque No.</td>
                                        <td class="cell tight center" style="width: 16%;">Date</td>
                                        <td class="cell tight center" style="width: 16%;">Bank</td>
                                        <td class="cell tight center" style="width: 16%;">Others</td>
                                    </tr>
                                    <tr>
                                        <td class="cell tight h-xxs">&nbsp;</td>
                                        <td class="cell tight h-xxs">&nbsp;</td>
                                        <td class="cell tight h-xxs">&nbsp;</td>
                                        <td class="cell tight h-xxs">&nbsp;</td>
                                        <td class="cell tight h-xxs">&nbsp;</td>
                                        <td class="cell tight h-xxs">&nbsp;</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td class="cell tight" colspan="2">
                                <div class="label">Basis of Charges as per G. C. Note or Contract No. :</div>
                                <div class="value">&nbsp;</div>
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
                                <th style="width: 5mm;">Sl.<br>No.</th>
                                <th style="width: 22mm;">Consignment / Old Bill<br>Number</th>
                                <th style="width: 16mm;">Date</th>
                                <th style="width: 18mm;">Invoice<br>No.</th>
                                <th style="width: 20mm;">Destination</th>
                                <th style="width: 20mm;">Vehicle No.</th>
                                <th style="width: 10mm;">Pkg.</th>
                                <th style="width: 22mm;">Charged<br>Weight Kgs.</th>
                                <th style="width: 16mm;">Rate</th>
                                <th style="width: 18mm;">Other Charge</th>
                                <th style="width: 16mm;">DD Charge</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="row-h">
                                    <td class="center">{{ $loop->iteration }}</td>
                                    <td class="center">{{ $row->consignment_no }}</td>
                                    <td class="center">{{ $row->old_bill_date ? \Carbon\Carbon::parse($row->old_bill_date)->format('d M Y') : '' }}</td>
                                    <td class="center">{{ $row->invoice_no }}</td>
                                    <td class="center">{{ $row->destination }}</td>
                                    <td class="center">{{ $row->vehicle_no }}</td>
                                    <td class="right">{{ $row->pkg }}</td>
                                    <td class="right">{{ $row->charged_weight }}</td>
                                    <td class="right">{{ $row->rate }}</td>
                                    <td class="right">{{ $row->other_charge }}</td>
                                    <td class="right">{{ $row->dd_charge }}</td>
                                    <td class="right">{{ $row->amount }}</td>
                                </tr>
                            @endforeach
                            @for ($i = $rows->count(); $i < 10; $i++)
                                <tr class="row-h">
                                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </td>
            </tr>

            <tr>
                <td class="cell" style="width: 70%;">
                    <div class="label">Rupees in words :</div>
                    <div class="value">&nbsp;</div>
                </td>
                <td class="cell center" style="width: 30%;">
                    <div class="label">GRAND TOTAL</div>
                    <div class="value right" style="font-size: 12px; font-weight: 800;">{{ number_format($grandTotal, 2) }}</div>
                </td>
            </tr>

            <tr>
                <td class="cell" style="width: 62%;">
                    <div class="label">Enclosures :</div>
                    <div class="value">&nbsp;</div>
                    <div style="margin-top: 4mm; font-size: 8px;">
                        <div>1) Payment should be made by payee A/c Cheque /</div>
                        <div> D.D. Favour of "{{ $companyName }}"</div>
                        <div>2) Interest @ 10% per annum will be charged if bill</div>
                        <div> not paid that? days from date of bill</div>
                    </div>
                </td>
                <td class="cell" style="width: 38%; padding: 0;">
                    <table>
                        <tr>
                            <td class="cell center" style="width: 30mm;">
                                <div class="label">EMP Code</div>
                                <div class="value">&nbsp;</div>
                            </td>
                            <td class="cell center">
                                <div style="font-weight: 800;">For {{ $companyName }}</div>
                                <div style="height: 12mm;"></div>
                                <div class="label">Signature</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="cell center">
                                <div class="label">Prepared by :</div>
                                <div class="value">&nbsp;</div>
                            </td>
                            <td class="cell center">
                                <div class="label">Checked by :</div>
                                <div class="value">&nbsp;</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

