@php
    $normalize = fn ($value) => strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $value));
    $fieldValue = function ($fields, $keys) use ($normalize) {
        $keys = array_map($normalize, (array) $keys);
        $matchedValue = null;

        foreach ($fields as $field) {
            $customField = $field->customField ?? null;
            $labels = [
                $customField->label ?? '',
                $customField->name ?? '',
            ];

            $matches = collect($labels)
                ->contains(fn ($label) => in_array($normalize($label), $keys, true));

            if (! $matches) {
                continue;
            }

            if (trim((string) $field->defaultAnswer) !== '') {
                return (string) $field->defaultAnswer;
            }

            $matchedValue = (string) $field->defaultAnswer;
        }

        return $matchedValue ?? '';
    };
    $fv = function ($keys, $fallback = '') use ($fieldValue, $invoice) {
        $value = $fieldValue($invoice->fields, $keys);

        return trim((string) $value) === '' ? $fallback : $value;
    };
    $v = fn ($keys, $fallback = '') => $fv($keys, $fallback);
    $number = function ($keys) use ($fv) {
        $value = trim(str_replace(',', '', (string) $fv($keys)));

        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return (float) (int) $amount === $amount ? (int) $amount : $amount;
    };
    $sumAmounts = function (array $values) {
        $values = collect($values)->filter(fn ($value) => $value !== null);

        return $values->isEmpty() ? null : $values->sum();
    };
    $formatAmount = function ($value) {
        if ($value === null || $value === '') {
            return '';
        }

        if (! is_numeric($value)) {
            return (string) $value;
        }

        $amount = (float) $value;

        return (string) ((float) (int) $amount === $amount ? (int) $amount : $amount);
    };
    $splitLines = function ($value, array $widths) {
        $value = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));
        $lines = [];
        foreach (explode("\n", $value) as $rawLine) {
            $rawLine = trim(preg_replace('/[ \t]+/', ' ', $rawLine));
            if ($rawLine === '') {
                continue;
            }
            $lineWidth = $widths[min(count($lines), count($widths) - 1)];
            foreach (explode("\n", wordwrap($rawLine, $lineWidth, "\n", true)) as $wrappedLine) {
                if (count($lines) >= count($widths)) {
                    $lines[count($widths) - 1] = trim($lines[count($widths) - 1].' '.$wrappedLine);
                    continue;
                }
                $lines[] = $wrappedLine;
            }
        }

        return array_pad(array_slice($lines, 0, count($widths)), count($widths), '');
    };
    $addressLine = fn ($keys, array $widths, int $index) => $splitLines($v($keys), $widths)[$index] ?? '';
    $numberToWords = function ($num) use (&$numberToWords) {
        $num = (int) $num;
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        if ($num === 0) {
            return 'Zero';
        }
        if ($num < 20) {
            return $ones[$num];
        }
        if ($num < 100) {
            return trim($tens[intdiv($num, 10)].' '.$ones[$num % 10]);
        }
        if ($num < 1000) {
            return trim($ones[intdiv($num, 100)].' Hundred '.($num % 100 ? $numberToWords($num % 100) : ''));
        }
        foreach ([10000000 => 'Crore', 100000 => 'Lakh', 1000 => 'Thousand'] as $divisor => $label) {
            if ($num >= $divisor) {
                return trim($numberToWords(intdiv($num, $divisor)).' '.$label.' '.($num % $divisor ? $numberToWords($num % $divisor) : ''));
            }
        }
    };
    $rupeesInWords = fn ($value) => $value === '' || $value === null ? '' : trim($numberToWords(round((float) str_replace(',', '', (string) $value))).' Rupees Only');
    $lorryHireAmount = $fv(['Lorry Hire', 'Lorry Hire Amount']);
    $otherChargesAmount = $fv(['Add Other Charges', 'Other Charges Amount']);
    $advanceAmount = $fv(['Advance Paid Rs', 'Advance Amount']);
    $lorryHireNumber = $number(['Lorry Hire', 'Lorry Hire Amount']);
    $otherChargesNumber = $number(['Add Other Charges', 'Other Charges Amount']);
    $advanceNumber = $number(['Advance Paid Rs', 'Advance Amount']) ?? 0;
    $grossHireNumber = $number(['Gross Hire Amount', 'Gross Hire Rupees'])
        ?? $sumAmounts([$lorryHireNumber, $otherChargesNumber]);
    $grossHireRupees = $formatAmount($grossHireNumber);
    $balanceNumber = $number(['Balance Amount', 'Balance Rupees']);
    if ($balanceNumber === null && $grossHireNumber !== null) {
        $balanceNumber = $grossHireNumber - $advanceNumber;
    }
    $balanceAmount = $formatAmount($balanceNumber);
    $grossHireRupeesOnly = $fv('Gross Hire Rupees') ?: $rupeesInWords($grossHireRupees);
    $balanceRupeesOnly = $fv('Balance Rupees Only') ?: $rupeesInWords($balanceAmount);
    $detentionAmount = $fv(['Add Detention Rs.', 'Detention Amount']);
    $extraHireAmount = $fv(['Extra Hire Rs', 'Extra Hire Amount']);
    $finalOtherAmount = $fv(['Other Rs', 'Final Other Amount']);
    $lessAdvanceOtherBranchAmount = $fv(['Less Adv. at other branch', 'Less Advance Other Branch Amount']);
    $lessDeductionClaimsAmount = $fv(['Less Deduction for Claims', 'Less Deduction Claims Amount']);
    $detentionNumber = $number(['Add Detention Rs.', 'Detention Amount']);
    $extraHireNumber = $number(['Extra Hire Rs', 'Extra Hire Amount']);
    $finalOtherNumber = $number(['Other Rs', 'Final Other Amount']);
    $lessAdvanceOtherBranchNumber = $number(['Less Adv. at other branch', 'Less Advance Other Branch Amount']);
    $lessDeductionClaimsNumber = $number(['Less Deduction for Claims', 'Less Deduction Claims Amount']);
    $hasFinalPaymentOperation = collect([
        $detentionNumber,
        $extraHireNumber,
        $finalOtherNumber,
        $lessAdvanceOtherBranchNumber,
        $lessDeductionClaimsNumber,
        $number(['Final Total Extra Amount']),
        $number(['Grand Total']),
        $number(['Total Less Amount']),
        $number(['Net Amount Payable']),
    ])->contains(fn ($value) => $value !== null);
    $finalTotalExtraNumber = $number(['Final Total Extra Amount'])
        ?? $sumAmounts([$detentionNumber, $extraHireNumber, $finalOtherNumber]);
    $grandTotalNumber = $number(['Grand Total']);
    if ($grandTotalNumber === null && $hasFinalPaymentOperation && $balanceNumber !== null) {
        $grandTotalNumber = $balanceNumber + ($finalTotalExtraNumber ?? 0);
    }
    $deductionTotalNumber = $number(['Total Less Amount'])
        ?? $sumAmounts([$lessAdvanceOtherBranchNumber, $lessDeductionClaimsNumber]);
    if ($deductionTotalNumber === null && $hasFinalPaymentOperation) {
        $deductionTotalNumber = 0;
    }
    $netAmountPayableNumber = $number(['Net Amount Payable']);
    if ($netAmountPayableNumber === null && $grandTotalNumber !== null) {
        $netAmountPayableNumber = $grandTotalNumber - ($deductionTotalNumber ?? 0);
    }
    $finalTotalExtraAmount = $formatAmount($finalTotalExtraNumber);
    $grandTotalAmount = $formatAmount($grandTotalNumber);
    $totalLessAmount = $formatAmount($deductionTotalNumber);
    $netAmountPayable = $formatAmount($netAmountPayableNumber);
    $finalRupeesValue = $netAmountPayable !== '' ? $netAmountPayable : '';
    $finalRupeesOnly = $fv('Final Rupees Only') ?: $rupeesInWords($finalRupeesValue);
    $company = $invoice->company ?? ($lorryReceipt->company ?? null);
    $companyName = $company?->name ?: '';
    $companyInitials = collect(preg_split('/\s+/', trim($companyName)))
        ->filter()
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
    $companyTagline = $company?->tagline ?: '';
    $companyPhone = $company?->address?->phone ?: '';
    $companyId = $invoice->company_id ?? ($company->id ?? null);
    $companyEmail = $companyId ? \App\Models\CompanySetting::getSetting('notification_email', $companyId) : '';
    $logo = $logo ?? ($company->logo_path ?? null);
    $displayCompanyAddress = trim(strip_tags((string) ($company_address ?? '')))
        ? preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $company_address)
        : '';
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*E-?mail\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*Mob(?:ile)?\.?\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    if ($companyPhone) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($companyPhone, '/').'\s*/i', '', $displayCompanyAddress);
    }
    if ($companyEmail) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($companyEmail, '/').'\s*/i', '', $displayCompanyAddress);
    }
    if ($displayCompanyAddress === '' && $company) {
        $address = $company->address;
        $displayCompanyAddress = implode('<br>', array_filter([
            e($address?->address_street_1),
            e($address?->address_street_2),
            e(trim(implode(' ', array_filter([$address?->city, $address?->state, $address?->zip])))),
            e($address?->country_name),
        ]));
    }
    $cTotalAmount = $formatAmount($number('Gross Hire Amount') ?? $grossHireNumber);
    $imageDataUri = function ($path) {
        if (! $path || ! file_exists($path)) {
            return null;
        }
        $mime = mime_content_type($path) ?: '';
        if (! str_starts_with($mime, 'image/')) {
            return null;
        }
        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    };
    $lorryAttachments = collect($lorryDocumentCollections ?? [])->map(function ($label, $collection) use ($invoice, $imageDataUri) {
        if (! method_exists($invoice, 'getFirstMedia')) {
            return null;
        }
        $media = $invoice->getFirstMedia($collection);
        if (! $media) {
            return null;
        }
        return ['label' => $label, 'name' => $media->file_name, 'mime' => $media->mime_type, 'image' => $imageDataUri($media->getPath())];
    })->filter()->values();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lorry Receipt - {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; size: 612pt 1008pt; }
        * { box-sizing: border-box; }
        body { color: #222; font-family: Arial, Helvetica, sans-serif; margin: 0; }
        .sheet { border: 1.35pt solid #222; height: 887pt; left: 43pt; position: absolute; top: 106pt; width: 520pt; }
        .a { position: absolute; }
        .box { border: .75pt solid #222; position: absolute; }
        .section-box { border: 1.15pt solid #222; position: absolute; }
        .section-outline { border: 1.2pt solid #111; position: absolute; }
        .sig-box { border: .75pt solid #222; position: absolute; text-align: center; }
        .top { border-top: 1.15pt solid #222; left: 0; position: absolute; width: 520pt; }
        .v { border-left: .75pt solid #222; position: absolute; }
        .h { border-top: .75pt solid #222; position: absolute; }
        .t { font-size: 7.8pt; line-height: 9.2pt; position: absolute; white-space: nowrap; }
        .small { font-size: 6.6pt; line-height: 7.6pt; }
        .tiny { font-size: 5.3pt; line-height: 6pt; }
        .b { font-weight: bold; }
        .c { text-align: center; }
        .r { text-align: right; }
        .brand { font-family: "Arial Narrow", Arial, Helvetica, sans-serif; font-size: 17.8pt; font-weight: bold; line-height: 18.5pt; white-space: nowrap; }
        .sub { font-size: 8.2pt; font-weight: bold; line-height: 9pt; }
        .logo { max-height: 63pt; max-width: 96pt; }
        .letter { font-size: 14pt; font-weight: bold; line-height: 14pt; position: absolute; }
        .title { font-size: 10.5pt; font-weight: bold; line-height: 11.5pt; position: absolute; }
        .line { border-bottom: .7pt solid #222; font-size: 5.4pt; font-weight: bold; height: 8.2pt; line-height: 6.8pt; overflow: hidden; padding-left: 1pt; position: absolute; white-space: nowrap; }
        .sig { border-top: .75pt solid #222; height: 1pt; position: absolute; }
        .mini-box { border: .75pt solid #222; font-size: 7pt; height: 12pt; line-height: 10pt; padding-left: 4pt; position: absolute; }
        .e-label { font-size: 6.6pt; line-height: 7.4pt; overflow: hidden; position: absolute; text-align: right; white-space: nowrap; }
        .e-rs { font-size: 6.8pt; font-weight: bold; line-height: 7.4pt; overflow: hidden; position: absolute; white-space: nowrap; }
        .e-amt { font-size: 5.2pt; font-weight: bold; line-height: 6pt; overflow: hidden; position: absolute; white-space: nowrap; }
        .attachments-page { page-break-before: always; padding: 30pt 34pt; }
        .attachments-title { font-size: 15pt; font-weight: bold; margin-bottom: 14pt; text-align: center; }
        .attachments-grid { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .attachment-cell { border: .8pt solid #222; height: 274pt; padding: 8pt; vertical-align: top; width: 50%; }
        .attachment-label { font-size: 9pt; font-weight: bold; margin-bottom: 6pt; }
        .attachment-image { max-height: 234pt; max-width: 238pt; }
        .attachment-pdf { border: .7pt solid #777; font-size: 9pt; height: 228pt; padding-top: 82pt; text-align: center; }
        .attachment-name { font-size: 7pt; margin-top: 5pt; word-break: break-all; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="a c" style="left:12pt; top:9pt; width:93pt;">@if($logo && file_exists($logo))<img class="logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}">@else<div class="brand">{{ $companyInitials }}</div>@endif</div>
    <div class="a c" style="left:124pt; top:2pt; width:286pt;">
        <div class="brand">{{ $companyName }}</div>
        <div class="sub">{{ $companyTagline }}</div>
        <div class="small" style="width:285pt;">{!! $displayCompanyAddress !!}</div>
    </div>
    <div class="t r" style="left:410pt; top:10pt; width:105pt;">@if($companyPhone)Mob. {{ $companyPhone }}@endif @if($companyEmail)<br>E-mail : {{ $companyEmail }}@endif</div>

    <div class="box" style="left:284pt; top:55pt; width:236pt; height:102pt;"></div>
    <div class="v" style="left:349pt; top:55pt; height:102pt;"></div><div class="v" style="left:396pt; top:55pt; height:102pt;"></div><div class="v" style="left:458pt; top:55pt; height:102pt;"></div>
    <div class="h" style="left:284pt; top:88pt; width:236pt;"></div><div class="h" style="left:284pt; top:123pt; width:236pt;"></div><div class="h" style="left:458pt; top:72pt; width:62pt;"></div><div class="h" style="left:458pt; top:107pt; width:62pt;"></div>
    <div class="t c" style="left:289pt; top:68pt; width:55pt;">Challan No.<br><span class="b">{{ $invoice->invoice_number }}</span></div>
    <div class="t c" style="left:352pt; top:62pt; width:39pt;">No. Of<br>Pages<br><span class="b">{{ $v('No Of Pages') }}</span></div>
    <div class="t c" style="left:402pt; top:68pt; width:48pt;">No. Pkgs.<br><span class="b">{{ $v('No Of Packages') }}</span></div>
    <div class="t c" style="left:462pt; top:58pt; width:54pt; line-height:7.2pt;">Actual Wt.<br><span class="b" style="display:block; font-size:5.6pt; line-height:5.8pt; margin-top:1.2pt;">{{ $v('Actual Weight') }}</span></div>
    <div class="t c" style="left:462pt; top:92pt; width:54pt; line-height:7.2pt;">Charge Wt.<br><span class="b" style="display:block; font-size:5.6pt; line-height:5.8pt; margin-top:1.2pt;">{{ $v('Charge Weight') }}</span></div>
    <div class="t c" style="left:302pt; top:124pt; width:45pt;">Lorry No.<br><span class="b">{{ $v('Lorry No') }}</span></div><div class="t c" style="left:354pt; top:124pt; width:37pt;">Rate<br><span class="b">{{ $v('Rate') }}</span></div><div class="t c" style="left:464pt; top:124pt; width:48pt;">Dist. Kms.<br><span class="b">{{ $v('Distance Kms') }}</span></div>

    <div class="t b" style="left:5pt; top:98pt;">Lorry Hire Contract No.</div><div class="line" style="left:105pt; top:97pt; width:172pt;">{{ $v('Lorry Hire Contract No') }}</div>
    <div class="box" style="left:42pt; top:111pt; width:235pt; height:44pt;"></div><div class="v" style="left:162pt; top:111pt; height:44pt;"></div><div class="h" style="left:42pt; top:133pt; width:235pt;"></div>
    <div class="t" style="left:12pt; top:119pt;">From</div><div class="t" style="left:49pt; top:119pt;">Code</div><div class="line" style="left:68pt; top:118pt; width:88pt;">{{ $v('From Code') }}</div><div class="t" style="left:166pt; top:119pt;">Name</div><div class="line" style="left:194pt; top:118pt; width:77pt;">{{ $v('From') }}</div>
    <div class="t" style="left:12pt; top:140pt;">To,</div><div class="t" style="left:49pt; top:140pt;">Code</div><div class="line" style="left:68pt; top:139pt; width:88pt;">{{ $v('To Code') }}</div><div class="t" style="left:166pt; top:140pt;">Name</div><div class="line" style="left:194pt; top:139pt; width:77pt;">{{ $v('To') }}</div>
    <div class="top" style="top:157pt;"></div>

    <div class="section-outline" style="left:0; top:157pt; width:520pt; height:130pt;"></div><div class="section-box" style="left:0; top:157pt; width:18pt; height:16pt;"></div><div class="letter" style="left:3pt; top:161pt;">A</div><div class="section-box" style="left:18pt; top:157pt; width:502pt; height:16pt;"></div><div class="title c" style="left:188pt; top:162pt; width:160pt;">VEHICLE PARTICULARS</div>
    <div class="t" style="left:6pt; top:183pt;">Regd at</div><div class="line" style="left:37pt; top:182pt; width:70pt;">{{ $v(['Regd at', 'Registered At']) }}</div><div class="t" style="left:108pt; top:183pt;">Body Type</div><div class="line" style="left:149pt; top:182pt; width:72pt;">{{ $v('Body Type') }}</div><div class="t" style="left:223pt; top:183pt;">Make</div><div class="line" style="left:247pt; top:182pt; width:82pt;">{{ $v('Make') }}</div><div class="t" style="left:331pt; top:183pt;">Model</div><div class="line" style="left:359pt; top:182pt; width:67pt;">{{ $v('Model') }}</div><div class="t" style="left:428pt; top:183pt;">Colour</div><div class="line" style="left:457pt; top:182pt; width:58pt;">{{ $v('Colour') }}</div>
    <div class="t" style="left:6pt; top:202pt;">Chasis No.</div><div class="line" style="left:46pt; top:201pt; width:150pt;">{{ $v('Chasis No') }}</div><div class="t" style="left:198pt; top:202pt;">Engine No.</div><div class="line" style="left:241pt; top:201pt; width:107pt;">{{ $v('Engine No') }}</div><div class="t" style="left:350pt; top:202pt;">Fitness Validity</div><div class="line" style="left:412pt; top:201pt; width:70pt;"></div><div class="t" style="left:482pt; top:202pt;">20</div><div class="line" style="left:494pt; top:201pt; width:22pt;"></div>
    <div class="t" style="left:6pt; top:223pt;">Road Permit No.</div><div class="line" style="left:65pt; top:222pt; width:190pt;"></div><div class="t" style="left:257pt; top:223pt;">Dt.</div><div class="line" style="left:271pt; top:222pt; width:85pt;"></div><div class="t" style="left:358pt; top:223pt;">Valid in</div><div class="line" style="left:389pt; top:222pt; width:127pt;"></div>
    <div class="line" style="left:6pt; top:243pt; width:317pt;">&nbsp;</div><div class="t" style="left:325pt; top:244pt;">Status upto</div><div class="line" style="left:371pt; top:243pt; width:145pt;"></div>
    <div class="t" style="left:6pt; top:258pt;">Insured with</div><div class="line" style="left:52pt; top:257pt; width:271pt;"></div><div class="t" style="left:325pt; top:258pt;">Divn. No.</div><div class="line" style="left:362pt; top:257pt; width:154pt;"></div>
    <div class="t" style="left:6pt; top:278pt;">Insurance Certificate No.</div><div class="line" style="left:94pt; top:277pt; width:229pt;"></div><div class="t" style="left:325pt; top:278pt;">Valid upto</div><div class="line" style="left:368pt; top:277pt; width:105pt;"></div><div class="t" style="left:474pt; top:278pt;">20</div><div class="line" style="left:487pt; top:277pt; width:29pt;"></div>
    <div class="top" style="top:287pt;"></div>

    <div class="section-outline" style="left:0; top:287pt; width:520pt; height:130pt;"></div><div class="v" style="left:172pt; top:287pt; height:130pt;"></div><div class="v" style="left:344pt; top:287pt; height:130pt;"></div>
    <div class="section-box" style="left:0; top:287pt; width:18pt; height:16pt;"></div><div class="letter" style="left:3pt; top:291pt;">B</div><div class="title" style="left:30pt; top:292pt;">OWNER</div><div class="box" style="left:72pt; top:287pt; width:99pt; height:16pt;"></div><div class="t" style="left:75pt; top:291pt;">Code</div><div class="title" style="left:178pt; top:292pt;">DRIVER</div><div class="title" style="left:351pt; top:292pt;">BROKER</div><div class="box" style="left:410pt; top:287pt; width:110pt; height:16pt;"></div>
    <div class="t" style="left:6pt; top:310pt;">Name</div><div class="line" style="left:28pt; top:309pt; width:139pt;">{{ $v('Owner Name') }}</div><div class="t" style="left:6pt; top:325pt;">Full Address</div><div class="line" style="left:57pt; top:324pt; width:110pt;">{{ $addressLine('Owner Address', [23, 34, 34], 0) }}</div><div class="line" style="left:6pt; top:339pt; width:161pt;">{{ $addressLine('Owner Address', [23, 34, 34], 1) }}</div><div class="line" style="left:6pt; top:354pt; width:161pt;">{{ $addressLine('Owner Address', [23, 34, 34], 2) }}</div><div class="t" style="left:6pt; top:369pt;">Phone No.</div><div class="line" style="left:43pt; top:368pt; width:124pt;">{{ $v('Owner Phone No') }}</div><div class="t" style="left:6pt; top:384pt;">Financer Name</div><div class="line" style="left:64pt; top:383pt; width:103pt;">{{ $v('Financer Name') }}</div><div class="t" style="left:6pt; top:399pt;">Address</div><div class="line" style="left:37pt; top:398pt; width:130pt;">{{ $v('Financer Address') }}</div>
    <div class="t" style="left:178pt; top:310pt;">Name</div><div class="line" style="left:201pt; top:309pt; width:137pt;">{{ $v('Driver Name') }}</div><div class="t" style="left:178pt; top:325pt;">Full Address</div><div class="line" style="left:229pt; top:324pt; width:109pt;">{{ $addressLine('Driver Address', [23, 34], 0) }}</div><div class="line" style="left:178pt; top:339pt; width:161pt;">{{ $addressLine('Driver Address', [23, 34], 1) }}</div><div class="t" style="left:178pt; top:354pt;">Name of Place</div><div class="line" style="left:236pt; top:353pt; width:102pt;">{{ $v('Driver Place') }}</div><div class="t" style="left:178pt; top:369pt;">Licence No.</div><div class="line" style="left:224pt; top:368pt; width:114pt;">{{ $v('Driver Licence No') }}</div><div class="t" style="left:178pt; top:384pt;">Dt.</div><div class="line" style="left:191pt; top:383pt; width:58pt;">{{ $v('Driver Licence Date') }}</div><div class="t" style="left:252pt; top:384pt;">Issued</div><div class="line" style="left:285pt; top:383pt; width:53pt;">{{ $v('Driver Licence Issued By') }}</div><div class="line" style="left:178pt; top:397pt; width:138pt;">{{ $v('Driver RTO') }}</div><div class="t" style="left:319pt; top:398pt;">RTO</div><div class="t" style="left:178pt; top:407pt;">Valid up Dt.</div><div class="line" style="left:229pt; top:406pt; width:109pt;">{{ $v('Driver Valid Up To') }}</div>
    <div class="line" style="left:350pt; top:309pt; width:160pt;">{{ $v('Broker Name') }}</div><div class="t" style="left:350pt; top:325pt;">Name & Add</div><div class="line" style="left:399pt; top:324pt; width:111pt;">{{ $addressLine('Broker Address', [23, 34], 0) }}</div><div class="line" style="left:350pt; top:339pt; width:160pt;">{{ $addressLine('Broker Address', [23, 34], 1) }}</div><div class="t" style="left:350pt; top:354pt;">Advice No.</div><div class="line" style="left:394pt; top:353pt; width:72pt;">{{ $v('Advice No') }}</div><div class="t" style="left:468pt; top:354pt;">Dt.</div><div class="line" style="left:484pt; top:353pt; width:29pt;">{{ $v('Advice Date') }}</div><div class="line" style="left:350pt; top:368pt; width:160pt;">&nbsp;</div><div class="t" style="left:350pt; top:384pt;">Desti. Broker Name</div><div class="line" style="left:424pt; top:383pt; width:86pt;">{{ $v('Destination Broker Name') }}</div><div class="t" style="left:350pt; top:399pt;">Add</div><div class="line" style="left:367pt; top:398pt; width:143pt;">{{ $v('Destination Broker Address') }}</div><div class="t" style="left:350pt; top:407pt;">Phone No.</div><div class="line" style="left:389pt; top:406pt; width:121pt;">{{ $v('Broker Phone No') }}</div>
    <div class="top" style="top:417pt;"></div>

    <div class="section-outline" style="left:0; top:417pt; width:520pt; height:141pt;"></div><div class="section-box" style="left:0; top:417pt; width:18pt; height:16pt;"></div><div class="letter" style="left:3pt; top:420pt;">C</div><div class="section-box" style="left:18pt; top:417pt; width:502pt; height:16pt;"></div><div class="title c" style="left:180pt; top:421pt; width:150pt;">HIRE PARTICULARS</div><div class="v" style="left:430pt; top:417pt; height:141pt;"></div><div class="h" style="left:430pt; top:448pt; width:90pt;"></div><div class="h" style="left:430pt; top:464pt; width:90pt;"></div><div class="h" style="left:430pt; top:480pt; width:90pt;"></div><div class="h" style="left:430pt; top:496pt; width:90pt;"></div><div class="h" style="left:430pt; top:512pt; width:90pt;"></div>
    <div class="t" style="left:22pt; top:438pt;">Paid to Shri</div><div class="line" style="left:72pt; top:437pt; width:160pt;">{{ $v('Paid To') }}</div><div class="t" style="left:235pt; top:438pt;">Lorry Hire (Rate X Wt.)</div><div class="t r" style="left:305pt; top:454pt; width:92pt;">Add Other Charges</div><div class="t" style="left:22pt; top:476pt;">Gross Hire Rupees</div><div class="line" style="left:95pt; top:475pt; width:250pt;">{{ $grossHireRupeesOnly }}</div><div class="t" style="left:348pt; top:476pt;">Only</div><div class="t" style="left:22pt; top:490pt;">Advance Paid by Cash/Cheque No.</div><div class="line" style="left:156pt; top:489pt; width:52pt;">{{ $v(['Advance Paid by Cash/Cheque No', 'Advance Cash Cheque No']) }}</div><div class="t" style="left:212pt; top:490pt;">On</div><div class="line" style="left:224pt; top:489pt; width:86pt;">{{ $v('Advance On') }}</div><div class="t" style="left:313pt; top:490pt;">Bank</div><div class="line" style="left:335pt; top:489pt; width:82pt;">{{ $v(['Bank', 'Advance Bank']) }}</div><div class="t" style="left:22pt; top:503pt;">Balance Payable at</div><div class="line" style="left:95pt; top:502pt; width:48pt;">{{ $v(['Balance Payable at', 'Balance Payable At']) }}</div><div class="t" style="left:148pt; top:503pt;">Code</div><div class="t" style="left:218pt; top:503pt;">Rupees</div><div class="line" style="left:251pt; top:502pt; width:80pt;">{{ $balanceRupeesOnly }}</div><div class="line" style="left:22pt; top:517pt; width:328pt;">&nbsp;</div><div class="t" style="left:350pt; top:517pt;">Only</div>
    <div class="t b" style="left:405pt; top:437pt;">Rs.:</div><div class="t b tiny" style="left:440pt; top:437pt; width:70pt;">{{ $lorryHireAmount }}</div><div class="t b" style="left:405pt; top:453pt;">Rs.:</div><div class="t b tiny" style="left:440pt; top:453pt; width:70pt;">{{ $otherChargesAmount }}</div><div class="t b" style="left:405pt; top:469pt;">Rs.:</div><div class="t b tiny" style="left:440pt; top:469pt; width:70pt;">{{ $cTotalAmount }}</div><div class="t b" style="left:405pt; top:485pt;">Rs.:</div><div class="t b tiny" style="left:440pt; top:485pt; width:70pt;">{{ $advanceAmount }}</div><div class="t b" style="left:405pt; top:501pt;">Rs.:</div><div class="t b tiny" style="left:440pt; top:501pt; width:70pt;">{{ $balanceAmount }}</div>
    <div class="sig" style="left:22pt; top:543pt; width:83pt;"></div><div class="t c" style="left:38pt; top:548pt; width:70pt;">Passed by</div><div class="sig" style="left:167pt; top:543pt; width:105pt;"></div><div class="t c" style="left:184pt; top:548pt; width:80pt;">Certified by</div><div class="sig" style="left:318pt; top:543pt; width:97pt;"></div><div class="t c" style="left:334pt; top:548pt; width:80pt;">Prepared by</div><div class="sig-box" style="left:430pt; top:530pt; width:87pt; height:24pt; font-size:5pt; font-weight:bold; line-height:7pt; padding-top:2pt;">ADVANCE<br>RECD BY ME</div><div class="top" style="top:558pt;"></div>

    <div class="section-outline" style="left:0; top:558pt; width:520pt; height:35pt;"></div><div class="section-box" style="left:0; top:558pt; width:18pt; height:16pt;"></div><div class="letter" style="left:3pt; top:562pt;">D</div><div class="section-box" style="left:18pt; top:558pt; width:502pt; height:16pt;"></div><div class="title" style="left:34pt; top:564pt;">LOADING REMARKS</div><div class="t" style="left:275pt; top:577pt;">Loaded by</div><div class="line" style="left:317pt; top:577pt; width:197pt;">{{ $v('Loaded By') }}</div><div class="top" style="top:593pt;"></div>

    <div class="section-outline" style="left:0; top:593pt; width:520pt; height:159pt;"></div><div class="section-box" style="left:0; top:593pt; width:18pt; height:16pt;"></div><div class="letter" style="left:3pt; top:597pt;">E</div><div class="section-box" style="left:18pt; top:593pt; width:502pt; height:16pt;"></div><div class="title" style="left:34pt; top:598pt;">FINAL PAYMENT PARTICULARS</div>
    <div class="v" style="left:438pt; top:593pt; height:159pt;"></div><div class="h" style="left:438pt; top:627pt; width:82pt;"></div><div class="h" style="left:438pt; top:644pt; width:82pt;"></div><div class="h" style="left:438pt; top:661pt; width:82pt;"></div><div class="h" style="left:438pt; top:678pt; width:82pt;"></div><div class="h" style="left:438pt; top:695pt; width:82pt;"></div>

    <div class="t" style="left:22pt; top:626pt;">Paid to shri</div><div class="line" style="left:75pt; top:625pt; width:350pt;">{{ $v('Final Paid To') }}</div>
    <div class="e-label" style="left:318pt; top:631pt; width:96pt;">Balance Payable</div><div class="e-rs" style="left:419pt; top:631pt; width:17pt;">Rs.:</div><div class="e-amt" style="left:450pt; top:631pt; width:58pt;">{{ $balanceAmount }}</div>

    <div class="t" style="left:22pt; top:646pt;">Add&nbsp; Detention&nbsp; Rs.</div><div class="mini-box" style="left:96pt; top:645pt; width:42pt;">{{ $detentionAmount !== '' ? $detentionAmount : 'I' }}</div>
    <div class="t" style="left:146pt; top:646pt;">Extra&nbsp; Hire&nbsp; Rs.</div><div class="mini-box" style="left:204pt; top:645pt; width:42pt;">{{ $extraHireAmount !== '' ? $extraHireAmount : 'II' }}</div>
    <div class="t" style="left:256pt; top:646pt;">Other Rs.</div><div class="mini-box" style="left:294pt; top:645pt; width:38pt;">{{ $finalOtherAmount !== '' ? $finalOtherAmount : 'III' }}</div>
    <div class="e-label" style="left:336pt; top:647pt; width:78pt;">Total I+II+III</div><div class="e-rs" style="left:419pt; top:647pt; width:17pt;">Rs.:</div><div class="e-amt" style="left:450pt; top:647pt; width:58pt;">{{ $finalTotalExtraAmount }}</div>

    <div class="e-label" style="left:336pt; top:661pt; width:78pt;">Grand Total</div><div class="e-rs" style="left:419pt; top:661pt; width:17pt;">Rs.:</div><div class="e-amt" style="left:450pt; top:661pt; width:58pt;">{{ $grandTotalAmount }}</div>

    <div class="t" style="left:22pt; top:673pt;">Less Adv. at other branch</div><div class="mini-box" style="left:120pt; top:672pt; width:48pt;">{{ $lessAdvanceOtherBranchAmount !== '' ? $lessAdvanceOtherBranchAmount : 'IV' }}</div>
    <div class="t" style="left:188pt; top:673pt;">Less Deduction for Claims</div><div class="mini-box" style="left:284pt; top:672pt; width:38pt;">{{ $lessDeductionClaimsAmount !== '' ? $lessDeductionClaimsAmount : 'V' }}</div>
    <div class="e-label" style="left:336pt; top:674pt; width:78pt;">Total (IV+V)</div><div class="e-rs" style="left:419pt; top:675pt; width:17pt;">Rs.:</div><div class="e-amt" style="left:450pt; top:675pt; width:58pt;">{{ $totalLessAmount }}</div>

    <div class="t" style="left:22pt; top:687pt;">Final Balance Amount Paid at</div><div class="mini-box" style="left:135pt; top:686pt; width:50pt;">{{ $v(['Final Balance Amount Paid at', 'Final Balance Code']) ?: 'Code' }}</div>
    <div class="t" style="left:226pt; top:687pt;">On</div><div class="mini-box" style="left:242pt; top:686pt; width:64pt;">{{ $v('Final Balance Date') }}</div>
    <div class="e-label" style="left:310pt; top:688pt; width:104pt;">Net Amount payable</div><div class="e-rs" style="left:419pt; top:690pt; width:17pt;">Rs.:</div><div class="e-amt" style="left:450pt; top:690pt; width:58pt;">{{ $netAmountPayable }}</div>

    <div class="t" style="left:22pt; top:701pt;">Cash/Cheque No.</div><div class="line" style="left:88pt; top:700pt; width:160pt;">{{ $v(['Cash/Cheque No.', 'Final Cash Cheque No']) }}</div>
    <div class="t" style="left:250pt; top:701pt;">On</div><div class="line" style="left:263pt; top:700pt; width:72pt;">{{ $v(['Final Cash Cheque On', 'Final Balance Date']) }}</div><div class="t" style="left:340pt; top:701pt;">Bank</div><div class="line" style="left:362pt; top:700pt; width:54pt;">{{ $v('Final Bank') }}</div>
    <div class="t" style="left:22pt; top:715pt;">Rupees</div><div class="line" style="left:53pt; top:714pt; width:363pt;">{{ $finalRupeesOnly }}</div><div class="t" style="left:418pt; top:715pt;">Only</div>
    <div class="sig-box" style="left:438pt; top:724pt; width:80pt; height:24pt; font-size:4.8pt; font-weight:bold; line-height:6.5pt; padding-top:2pt;">FINAL PAYMENT<br>RECD BY ME</div>

    <div class="sig" style="left:20pt; top:735pt; width:80pt;"></div><div class="t c" style="left:30pt; top:740pt; width:75pt;">Passed by</div><div class="sig" style="left:157pt; top:735pt; width:105pt;"></div><div class="t c" style="left:172pt; top:740pt; width:80pt;">Certified by</div><div class="sig" style="left:315pt; top:735pt; width:90pt;"></div><div class="t c" style="left:326pt; top:740pt; width:85pt;">Prepared by</div><div class="top" style="top:752pt;"></div>

    <div class="title c" style="left:210pt; top:757pt; width:100pt;">UNDERTAKING</div><div class="t" style="left:7pt; top:774pt; width:506pt; white-space:normal; line-height:8.4pt;">Please pay the freight if the goods are delivered in full and in good and proper conditions, fulfilling all the terms and conditions.<br>Note : The weight noted in challan is mostly correct, it is the responsibility of the owner/driver to weight the vehicle before leaving the starting point. In no case the company should be held liable for damage of penalty whatsoever due to overloading but extra lorry hire may be paid by the company.</div><div class="t" style="left:7pt; top:821pt;">Recd. No. of Bilties</div><div class="box" style="left:7pt; top:835pt; width:225pt; height:44pt;"></div><div class="t b" style="left:13pt; top:842pt; width:210pt;">{{ $v('Received No Of Bilties') }}</div><div class="t c b" style="left:380pt; top:854pt; width:130pt;">ORIGINAL<br>PAYMENT COPY</div><div class="t b" style="left:8pt; top:878pt;">Note : <span style="font-weight:normal;">Payment will be made on only production of original copy.</span></div><div class="t b r" style="left:338pt; top:878pt; width:175pt;">Left Thumb Impression or Signature of Driver / Owner</div>
</div>

@if($lorryAttachments->isNotEmpty())
    <div class="attachments-page">
        <div class="attachments-title">Lorry Receipt Documents</div>
        <table class="attachments-grid" cellspacing="0" cellpadding="0">
            @foreach($lorryAttachments->chunk(2) as $row)
                <tr>
                    @foreach($row as $attachment)
                        <td class="attachment-cell">
                            <div class="attachment-label">{{ $attachment['label'] }}</div>
                            @if($attachment['image'])
                                <div class="c"><img class="attachment-image" src="{{ $attachment['image'] }}"></div>
                            @else
                                <div class="attachment-pdf">PDF document uploaded<div class="attachment-name">{{ $attachment['name'] }}</div></div>
                            @endif
                        </td>
                    @endforeach
                    @if($row->count() === 1)<td class="attachment-cell">&nbsp;</td>@endif
                </tr>
            @endforeach
        </table>
    </div>
@endif
</body>
</html>
