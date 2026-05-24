<!DOCTYPE html>
<html>

<head>
    <title>LR Receipt - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @php
        use Illuminate\Support\Str;

        $currency = $invoice->customer->currency ?? $invoice->currency;

        $normalizeFieldKey = function (?string $value): string {
            return Str::upper(Str::slug((string) $value, '_'));
        };
        $getCustomField = function ($model, string $modelType, string $slug, string $fallback = '') use ($normalizeFieldKey): string {
            if (! $model) {
                return $fallback;
            }

            $key = $normalizeFieldKey($slug);
            $modelTypes = collect([
                $modelType,
                Str::title(Str::lower($modelType)),
                Str::upper($modelType),
                Str::lower($modelType),
            ])->unique();

            $slugs = collect([$slug])
                ->merge($modelTypes->map(fn ($type) => 'CUSTOM_'.$type.'_'.$key))
                ->unique()
                ->values();

            foreach ($slugs as $fieldSlug) {
                $value = $model->getCustomFieldValueBySlug($fieldSlug);

                if ($value !== null && $value !== '') {
                    return (string) $value;
                }
            }

            foreach ($model->fields()->with('customField')->get() as $field) {
                $customField = $field->customField;

                if (! $customField) {
                    continue;
                }

                $candidates = collect([
                    $customField->slug,
                    $customField->name,
                    $customField->label,
                ])->map($normalizeFieldKey);

                if ($candidates->contains($key) || $candidates->contains('CUSTOM_'.$normalizeFieldKey($modelType).'_'.$key)) {
                    return (string) ($field->defaultAnswer ?? $fallback);
                }
            }

            return $fallback;
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
        $itm = function (string $slug, string $fallback = '') use ($itemField, $invoice): string {
            $item = $invoice->items->first();

            return $item ? $itemField($item, $slug, $fallback) : $fallback;
        };
        $money = function ($amount) use ($currency): string {
            return trim(strip_tags(format_money_pdf((int) $amount, $currency)));
        };

        $companyName = $invoice->company?->name ?? '';
        $companyPan = 'NBKPS0084L';
        $companyGstin = '24NBKPS0084L1ZZ';
        $companyPhone = $invoice->company?->address?->phone;
        $customerGstin = $invoice->customer?->tax_id ?: $cust('gstin');
        $consigneeName = $invoice->customer?->name ?: $inv('consignee');
        $consigneePhone = $invoice->customer?->phone ?: $inv('consignee_phone_no');
        $consigneeGstin = $invoice->customer?->tax_id ?: $inv('consignee_gst_no');
        $consignorName = $inv('consignor');
        $consignorPhone = $inv('consignor_phone_no');
        $consignorGstin = $inv('consignor_gst_no');

        $firstItem = $invoice->items->first();
        $totalPackages = (string) $invoice->items->sum(fn ($i) => (int) $i->quantity);

        // Shared view variables come from Invoice::getPDFData(). Guard against null/false.
        $company_address = $company_address ?: '';
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

        .box {
            border: 1px solid #111827;
        }

        .cell {
            border: 1px solid #111827;
            vertical-align: top;
            padding: 4pt 5pt;
        }

        .label {
            font-size: 8px;
            font-weight: 700;
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

        .muted {
            color: #374151;
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
            font-weight: 800;
            line-height: 18px;
        }

        .company-contact {
            font-size: 8px;
            line-height: 11px;
            margin-top: 3pt;
        }

        .section-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.2px;
            background: #f3f4f6;
            padding: 4pt 5pt;
            border: 1px solid #111827;
        }

        .party-cell {
            height: 76pt;
        }

        .mode-cell {
            font-size: 11px;
            font-weight: 800;
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <table class="box">
        <tr>
            <td class="cell" style="width: 62%; padding: 0;">
                <table class="company-header" style="padding: 4pt 5pt;">
                    <tr>
                        <td class="company-logo-cell">
                            @if ($logo)
                                <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                            @else
                                <div class="company-fallback-logo">{{ $companyName }}</div>
                            @endif
                        </td>
                        <td style="padding-left: 6pt;">
                            <div style="font-size: 18px; font-weight: 800;">{{ $companyName }}</div>
                            <div class="muted" style="margin-top: 2pt;">{!! $company_address !!}</div>
                            @if ($companyPhone)
                                <div class="company-contact"><span class="label">Phone / Mobile :</span> {{ $companyPhone }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="cell party-cell" style="width: 50%;">
                            <div class="label">Consignor</div>
                            <div class="value">{!! nl2br(e($consignorName)) !!}</div>
                            <div style="margin-top: 6pt;">
                                <span class="label">Phone No. :</span>
                                <span class="value">{{ $consignorPhone }}</span>
                            </div>
                            <div style="margin-top: 4pt;">
                                <span class="label">GST No. :</span>
                                <span class="value">{{ $consignorGstin }}</span>
                            </div>
                        </td>
                        <td class="cell party-cell" style="width: 50%;">
                            <div class="label">Consignee</div>
                            <div class="value">{!! nl2br(e($consigneeName)) !!}</div>
                            <div style="margin-top: 6pt;">
                                <span class="label">Phone No. :</span>
                                <span class="value">{{ $consigneePhone }}</span>
                            </div>
                            <div style="margin-top: 4pt;">
                                <span class="label">GST No. :</span>
                                <span class="value">{{ $consigneeGstin }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="cell" style="width: 38%;">
                <table>
                    <tr>
                        <td class="cell" style="width: 45%;">
                            <div class="label">Date :</div>
                            <div class="value">{{ $invoice->formattedInvoiceDate }}</div>
                        </td>
                        <td class="cell" style="width: 55%;">
                            <div class="label">Docket No. :</div>
                            <div class="value" style="font-weight: 800; font-size: 12px;">{{ $invoice->invoice_number }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">
                            <div class="label">Time :</div>
                            <div class="value">{{ $inv('time') }}</div>
                        </td>
                        <td class="cell">
                            <div class="label">From :</div>
                            <div class="value">{{ $inv('from') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">
                            <div class="label">OWNER'S RISK</div>
                        </td>
                        <td class="cell">
                            <div class="label">To :</div>
                            <div class="value">{{ $inv('to') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell" colspan="2">
                            <div class="label">Truck No. :</div>
                            <div class="value">{{ $inv('truck_no', $firstItem ? $itemField($firstItem, 'vehicle_no') : '') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell" colspan="2">
                            <div class="label">PAN No. :</div>
                            <div class="value">{{ $inv('company_pan', $companyPan) }}</div>
                            <div style="margin-top: 4pt;">
                                <span class="label">GSTIN :</span>
                                <span class="value">{{ $inv('company_gstin', $companyGstin) }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="cell" style="width: 62%; padding: 0;">
                <table>
                    <tr>
                        <td class="cell" style="width: 50%;">
                            <div class="label">Description of Goods</div>
                            <div class="value">{{ $itm('description_of_goods', $firstItem?->name ?? '') }}</div>
                        </td>
                        <td class="cell" style="width: 25%;">
                            <div class="label">No. of Articles</div>
                            <div class="value center">{{ $itm('no_of_articles', $totalPackages) }}</div>
                        </td>
                        <td class="cell" style="width: 25%;">
                            <div class="label">Packing</div>
                            <div class="value">{{ $itm('packing') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">
                            <div class="label">HSN CODE</div>
                            <div class="value">{{ $itm('hsn_code') }}</div>
                        </td>
                        <td class="cell">
                            <div class="label">Actual Weight</div>
                        </td>
                        <td class="cell">
                            <div class="value">{{ $itm('actual_weight') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">
                            <div class="label">Delivery At. :</div>
                            <div class="value">{{ $itm('delivery_at') }}</div>
                        </td>
                        <td class="cell">
                            <div class="label">Charged Weight</div>
                        </td>
                        <td class="cell">
                            <div class="value">{{ $itm('charged_weight') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">
                            <div class="label">E-way Bill No. :</div>
                            <div class="value">{{ $itm('e_way_bill_no') }}</div>
                        </td>
                        <td class="cell">
                            <div class="label">Invoice No. :</div>
                        </td>
                        <td class="cell">
                            <div class="value">{{ $itm('invoice_no', $invoice->invoice_number) }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">&nbsp;</td>
                        <td class="cell">
                            <div class="label">Goods Value</div>
                        </td>
                        <td class="cell">
                            <div class="value">{{ $itm('goods_value') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="cell">&nbsp;</td>
                        <td class="cell">
                            <div class="label">POD Required.</div>
                        </td>
                        <td class="cell">
                            <div class="value">{{ $itm('pod_required') }}</div>
                        </td>
                    </tr>
                </table>
            </td>

            <td class="cell" style="width: 38%; padding: 0;">
                <table>
                    <tr>
                        <td class="cell center" style="width: 44%;"><div class="label">Description of Freight</div></td>
                        <td class="cell center" style="width: 31%;"><div class="label">To Pay/Paid Rs.</div></td>
                        <td class="cell center" style="width: 25%;"><div class="label">Mode of Payment</div></td>
                    </tr>
                    @php
                        $freightRows = [
                            ['label' => 'Basic Freight', 'slug' => 'basic_freight'],
                            ['label' => 'Local Collection', 'slug' => 'local_collection'],
                            ['label' => 'Door Delivery', 'slug' => 'door_delivery'],
                            ['label' => 'Hamali', 'slug' => 'hamali'],
                            ['label' => 'Docket Charge', 'slug' => 'docket_charge'],
                            ['label' => 'Other Charge', 'slug' => 'other_charge'],
                            ['label' => 'F.O.V.', 'slug' => 'fov'],
                            ['label' => 'Sub Total', 'slug' => 'sub_total'],
                            ['label' => 'Net Amount', 'slug' => 'net_amount'],
                        ];
                    @endphp
                    @foreach ($freightRows as $row)
                        <tr>
                            <td class="cell">
                                <div class="label">{{ $row['label'] }}</div>
                            </td>
                            <td class="cell">
                                <div class="value right">
                                    @if ($row['slug'] === 'net_amount')
                                        {{ $itm('net_amount', $money($invoice->total)) }}
                                    @else
                                        {{ $itm($row['slug']) }}
                                    @endif
                                </div>
                            </td>
                            @if ($loop->first)
                                <td class="cell mode-cell" rowspan="{{ count($freightRows) }}">
                                    {{ strtoupper($inv('mode_of_payment')) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>

        <tr>
            <td class="cell" style="width: 62%;">
                <div class="label">Rubber Stamp and Signature of Consignee</div>
                <div style="height: 50pt;"></div>
                <div class="label">Phone / Mobile</div>
                <div class="value">{{ $consigneePhone }}</div>
            </td>
            <td class="cell center" style="width: 38%;">
                <div class="label">GST Tax Payable By</div>
                <div class="value">Consignor / Consignee</div>
                <div style="margin-top: 10pt; font-weight: 800;">For {{ $companyName }}</div>
            </td>
        </tr>
    </table>
</body>

</html>
