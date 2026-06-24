<!DOCTYPE html>
<html lang="en">

<head>
    <title>@lang('pdf_profit_loss_label')</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 20px;
        }

        .report-header {
            width: 100%;
        }

        .heading-text {
            font-weight: bold;
            font-size: 24px;
            color: #5851D8;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date-range {
            font-weight: normal;
            font-size: 15px;
            color: #A5ACC1;
            width: 100%;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: normal;
            font-size: 16px;
            color: #595959;
            padding: 0px;
            margin: 0px;
            margin-top: 6px;
        }

        .income-table {
            margin-top: 53px;
            width: 100%;
        }

        .income-title {
            padding: 0px;
            margin: 0px;
            font-size: 16px;
            line-height: 21px;
            color: #040405;
            text-align: left;
        }

        .income-amount {
            padding: 0px;
            margin: 0px;
            font-weight: bold;
            font-size: 16px;
            line-height: 21px;
            text-align: right;
            color: #040405;
            text-align: right;
        }

        .expenses-title {
            margin-top: 20px;
            padding-left: 3px;
            font-size: 16px;
            line-height: 21px;
            color: #040405;
        }

        .expenses-table-container {
            padding-left: 10px;
        }

        .expenses-table {
            width: 100%;
            padding-bottom: 10px;
        }

        .expense-title {
            padding: 0px;
            margin: 0px;
            font-size: 14px;
            line-height: 21px;
            color: #595959;
        }

        .expense-amount {
            padding: 0px;
            margin: 0px;
            font-size: 14px;
            line-height: 21px;
            text-align: right;
            color: #595959;
        }

        .expense-total-indicator-table {
            border-top: 1px solid #EAF1FB;
            width: 100%;
        }

        .expense-total-cell {
            padding-right: 20px;
            padding-top: 10px;
        }

        .expense-total {
            padding-top: 10px;
            padding-right: 30px;
            padding: 0px;
            margin: 0px;
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            line-height: 21px;
            text-align: right;
            color: #040405;
        }

        .report-footer {
            width: 100%;
            margin-top: 40px;
            padding: 15px 20px;
            background: #F9FBFF;
            box-sizing: border-box;
        }

        .report-footer-label {
            padding: 0px;
            margin: 0px;
            text-align: left;
            font-weight: bold;
            font-size: 16px;
            line-height: 21px;
            color: #595959;
        }

        .report-footer-value {
            padding: 0px;
            margin: 0px;
            text-align: right;
            font-weight: bold;
            font-size: 20px;
            line-height: 21px;
            color: #5851D8;
        }
    </style>

    @if (App::isLocale('th'))
    @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="sub-container">
        <table class="report-header">
            <tr>
                <td>
                    <p class="heading-text">{{ $company->name }}</p>
                </td>
                <td>
                    <p class="heading-date-range">{{ $from_date }} - {{ $to_date }}</p>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <p class="sub-heading-text">@lang('pdf_profit_loss_label')</p>
                </td>
            </tr>
        </table>

        <table class="income-table">
            <tr>
                <td>
                    <p class="income-title">@lang("pdf_income_label")</p>
                </td>
                <td>
                    <p class="income-amount">{!! format_money_pdf($income, $currency) !!}</p>
                </td>
            </tr>
        </table>


        <!-- Detailed Lorry Receipt Breakdown grouped by Customer -->
        @if(count($customersData) > 0)
            @foreach ($customersData as $customerData)
                <p style="margin-top: 30px; font-weight: bold; font-size: 14px; color: #040405; border-bottom: 1px solid #EAF1FB; padding-bottom: 4px;">
                    {{ $customerData['name'] }}
                </p>
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px;">
                    <thead>
                        <tr style="background-color: #F9FBFF; border-bottom: 1px solid #EAF1FB;">
                            <th style="padding: 6px 8px; text-align: left; font-size: 10px; font-weight: bold; color: #040405; width: 25%;">{{ __('Lorry Receipt details') }}</th>
                            <th style="padding: 6px 8px; text-align: right; font-size: 10px; font-weight: bold; color: #040405; width: 25%;">{{ __('Credit/Income Amount') }}</th>
                            <th style="padding: 6px 8px; text-align: right; font-size: 10px; font-weight: bold; color: #040405; width: 25%;">{{ __('Debit/Expense Amount') }}</th>
                            <th style="padding: 6px 8px; text-align: right; font-size: 10px; font-weight: bold; color: #040405; width: 25%;">{{ __('Net Profit / Loss') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customerData['lrReceipts'] as $lrReceipt)
                            @php
                                $netVal = (float) $lrReceipt['net_profit'];
                                $netColor = $netVal >= 0 ? '#2ec4b6' : '#e71d36'; // Teal for profit, Red for loss
                            @endphp
                            <tr style="border-bottom: 1px solid #EAF1FB;">
                                <td style="padding: 6px 8px; text-align: left; font-size: 10px; color: #595959;">
                                    {{ __('Docket No') }}: <strong>{{ $lrReceipt['lr_no'] }}</strong><br>
                                    @if(!empty($lrReceipt['challan_no']))
                                        <span style="font-size: 8px; color: #595959;">Challan No: {{ $lrReceipt['challan_no'] }}</span><br>
                                    @endif
                                    @if(!empty($lrReceipt['office_invoice_no']))
                                        <span style="font-size: 8px; color: #595959;">Invoice No: {{ $lrReceipt['office_invoice_no'] }}</span><br>
                                    @endif
                                    @if(!empty($lrReceipt['lr_date']))
                                        <span style="font-size: 8px; color: #a5acc1;">Date: {{ $lrReceipt['lr_date'] }}</span>
                                    @endif
                                </td>
                                <td style="padding: 6px 8px; text-align: right; font-size: 10px; color: #595959;">
                                    {!! format_money_pdf($lrReceipt['amount_credit'], $currency) !!}<br>
                                    @if(!empty($lrReceipt['amount_credit_date']))
                                        <span style="font-size: 8px; color: #a5acc1;">Credit Date: {{ $lrReceipt['amount_credit_date'] }}</span>
                                    @endif
                                </td>
                                <td style="padding: 6px 8px; text-align: right; font-size: 10px; color: #595959;">
                                    {!! format_money_pdf($lrReceipt['amount_debit'], $currency) !!}<br>
                                    @if(!empty($lrReceipt['amount_debit_date']))
                                        <span style="font-size: 8px; color: #a5acc1;">Debit Date: {{ $lrReceipt['amount_debit_date'] }}</span>
                                    @endif
                                </td>
                                <td style="padding: 6px 8px; text-align: right; font-size: 10px; font-weight: bold; color: {{ $netColor }};">
                                    {!! format_money_pdf($netVal, $currency) !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <table style="width: 100%; margin-bottom: 25px;">
                    <tr>
                        <td style="text-align: right; font-size: 11px; font-weight: bold; color: #040405;">
                            {{ __('Total Income') }}: {!! format_money_pdf($customerData['totalIncome'], $currency) !!}
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            {{ __('Net Profit') }}: {!! format_money_pdf($customerData['totalNetProfit'], $currency) !!}
                        </td>
                    </tr>
                </table>
            @endforeach
        @else
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 15px; text-align: center; font-size: 11px; color: #a5acc1;">
                        {{ __('No records found') }}
                    </td>
                </tr>
            </table>
        @endif
    </div>

    <table class="report-footer">
        <tr>
            <td>
                <p class="report-footer-label">@lang("pdf_net_profit_label")</p>
            </td>
            <td>
                <p class="report-footer-value">{!! format_money_pdf($netProfit, $currency) !!}</p>
            </td>
        </tr>
    </table>
</body>

</html>
