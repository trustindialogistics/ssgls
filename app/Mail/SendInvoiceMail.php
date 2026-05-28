<?php

namespace App\Mail;

use App\Facades\Hashids;
use App\Models\EmailLog;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendInvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $data = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $log = EmailLog::create([
            'from' => $this->data['from'],
            'to' => $this->data['to'],
            'cc' => $this->data['cc'] ?? null,
            'bcc' => $this->data['bcc'] ?? null,
            'subject' => $this->data['subject'],
            'body' => $this->data['body'],
            'mailable_type' => Invoice::class,
            'mailable_id' => $this->data['invoice']['id'],
        ]);

        $log->token = Hashids::connection(EmailLog::class)->encode($log->id);
        $log->save();

        $this->data['url'] = route('invoice', ['email_log' => $log->token]);

        $mailContent = $this->from($this->data['from'], config('mail.from.name'))
            ->subject($this->data['subject'])
            ->markdown('emails.send.invoice', ['data', $this->data]);

        if ($this->data['attach']['data']) {
            $fileName = $this->data['invoice']['invoice_number'];

            if (($this->data['invoice']['template_name'] ?? null) === 'lr_receipt') {
                $fileName = preg_replace('/^INV/i', 'DOC', $fileName);

                if (! empty($this->data['attach']['copy_type'])) {
                    $fileName .= '-'.$this->data['attach']['copy_type'];
                }
            }

            $mailContent->attachData(
                $this->data['attach']['data']->output(),
                $fileName.'.pdf'
            );
        }

        return $mailContent;
    }
}
