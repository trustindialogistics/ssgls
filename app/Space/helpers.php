<?php

use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\Setting;
use App\Space\InstallUtils;
use Illuminate\Support\Str;

if (! class_exists('ZipArchive', false)) {
    class ZipArchive
    {
        public const CM_STORE = 0;

        public const CM_DEFAULT = 0;

        public const CM_DEFLATE = 8;

        public const CM_BZIP2 = 12;

        public const CM_XZ = 95;

        public const CREATE = 1;

        public const RDONLY = 16;

        public const EM_AES_128 = 257;

        public const EM_AES_192 = 258;

        public const EM_AES_256 = 259;

        public function __call(string $name, array $arguments): never
        {
            throw new RuntimeException('The PHP zip extension is required to use ZipArchive.');
        }
    }
}

if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        $delimiter = '~';
        $regex = $delimiter.str_replace($delimiter, '\\'.$delimiter, $pattern).$delimiter.'u';

        return preg_split($regex, $string, $limit);
    }
}

if (! function_exists('mb_strimwidth')) {
    function mb_strimwidth(string $string, int $start, int $width, string $trim_marker = '', ?string $encoding = null): string
    {
        if (
            class_exists(\Symfony\Polyfill\Mbstring\Mbstring::class)
            && method_exists(\Symfony\Polyfill\Mbstring\Mbstring::class, 'mb_strimwidth')
        ) {
            return \Symfony\Polyfill\Mbstring\Mbstring::mb_strimwidth($string, $start, $width, $trim_marker, $encoding);
        }

        $encoding ??= 'UTF-8';
        $string = mb_substr($string, $start, null, $encoding);

        if (mb_strwidth($string, $encoding) <= $width) {
            return $string;
        }

        $length = max(0, $width - mb_strwidth($trim_marker, $encoding));
        $value = mb_substr($string, 0, $length, $encoding);

        while ($value !== '' && mb_strwidth($value, $encoding) > $length) {
            $value = mb_substr($value, 0, -1, $encoding);
        }

        return $value.$trim_marker;
    }
}

/**
 * Get company setting
 *
 * @return string
 */
function get_company_setting($key, $company_id)
{
    if (! InstallUtils::isDbCreated()) {
        return null;
    }

    return CompanySetting::getSetting($key, $company_id);
}

/**
 * Get app setting
 *
 * @param  $company_id
 * @return string
 */
function get_app_setting($key)
{
    if (! InstallUtils::isDbCreated()) {
        return null;
    }

    return Setting::getSetting($key);
}

/**
 * Get page title
 *
 * @return string
 */
function get_page_title($company_id)
{
    if (! InstallUtils::isDbCreated()) {
        return null;
    }

    $routeName = Route::currentRouteName();

    $defaultPageTitle = 'HisabKitabb Services';

    if ($routeName === 'customer.dashboard') {
        $pageTitle = CompanySetting::getSetting('customer_portal_page_title', $company_id);

        return $pageTitle ? $pageTitle : $defaultPageTitle;
    }

    $pageTitle = Setting::getSetting('admin_page_title');

    return $pageTitle ? $pageTitle : $defaultPageTitle;
}

/**
 * Set Active Path
 *
 * @param  string  $active
 * @return string
 */
function set_active($path, $active = 'active')
{
    return call_user_func_array('Request::is', (array) $path) ? $active : '';
}

/**
 * @return mixed
 */
function is_url($path)
{
    return call_user_func_array('Request::is', (array) $path);
}

/**
 * @return string
 */
function getCustomFieldValueKey(string $type)
{
    switch ($type) {
        case 'Input':
            return 'string_answer';

        case 'TextArea':
            return 'string_answer';

        case 'Phone':
            return 'number_answer';

        case 'Url':
            return 'string_answer';

        case 'Number':
            return 'number_answer';

        case 'Dropdown':
            return 'string_answer';

        case 'Switch':
            return 'boolean_answer';

        case 'Date':
            return 'date_answer';

        case 'Time':
            return 'time_answer';

        case 'DateTime':
            return 'date_time_answer';

        default:
            return 'string_answer';
    }
}

/**
 * @return formated_money
 */
function format_money_pdf($money, $currency = null)
{
    $money = $money / 100;

    if (! $currency) {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', 1));
    }

    $format_money = number_format(
        $money,
        $currency->precision,
        $currency->decimal_separator,
        $currency->thousand_separator
    );

    $currency_with_symbol = '';
    if ($currency->swap_currency_symbol) {
        $currency_with_symbol = $format_money.'<span style="font-family: DejaVu Sans;">'.$currency->symbol.'</span>';
    } else {
        $currency_with_symbol = '<span style="font-family: DejaVu Sans;">'.$currency->symbol.'</span>'.$format_money;
    }

    return $currency_with_symbol;
}

/**
 * @param  $string
 * @return string
 */
function clean_slug($model, $title, $id = 0)
{
    // Normalize the title
    $slug = Str::upper('CUSTOM_'.$model.'_'.Str::slug($title, '_'));

    // Get any that could possibly be related.
    // This cuts the queries down by doing it once.
    $allSlugs = getRelatedSlugs($model, $slug, $id);

    // If we haven't used it before then we are all good.
    if (! $allSlugs->contains('slug', $slug)) {
        return $slug;
    }

    // Just append numbers like a savage until we find not used.
    for ($i = 1; $i <= 10; $i++) {
        $newSlug = $slug.'_'.$i;
        if (! $allSlugs->contains('slug', $newSlug)) {
            return $newSlug;
        }
    }

    throw new Exception('Can not create a unique slug');
}

function getRelatedSlugs($type, $slug, $id = 0)
{
    return CustomField::select('slug')->where('slug', 'like', $slug.'%')
        ->where('model_type', $type)
        ->where('id', '<>', $id)
        ->get();
}

function respondJson($error, $message)
{
    return response()->json([
        'error' => $error,
        'message' => $message,
    ], 422);
}
