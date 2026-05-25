@component('mail::message')
@lang('mail_viewed_estimate', ['name' => $data['user']['name']])

@lang('mail_thanks'),<br>
{{ config('app.name') }}
@endcomponent
