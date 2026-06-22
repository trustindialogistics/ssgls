<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date,
            'notes' => $this->getNotes(),
            'amount' => $this->amount,
            'tds_amount' => $this->tds_amount,
            'deduction_amount' => $this->deduction_amount,
            'invoice_paid_status' => $this->invoice_paid_status,
            'unique_hash' => $this->unique_hash,
            'invoice_id' => $this->invoice_id,
            'company_id' => $this->company_id,
            'payment_method_id' => $this->payment_method_id,
            'creator_id' => $this->creator_id,
            'updated_by' => $this->updated_by,
            'customer_id' => $this->customer_id,
            'creator' => $this->whenLoaded('creator', function () {
                return new UserResource($this->creator);
            }),
            'updatedBy' => $this->whenLoaded('updatedBy', function () {
                return new UserResource($this->updatedBy);
            }),
            'exchange_rate' => $this->exchange_rate,
            'base_amount' => $this->base_amount,
            'currency_id' => $this->currency_id,
            'transaction_id' => $this->transaction_id,
            'sequence_number' => $this->sequence_number,
            'formatted_created_at' => $this->formattedCreatedAt,
            'formatted_payment_date' => $this->formattedPaymentDate,
            'payment_pdf_url' => $this->paymentPdfUrl,
            'customer' => $this->whenLoaded('customer', function () {
                return new CustomerResource($this->customer);
            }),
            'invoice' => $this->whenLoaded('invoice', function () {
                return new InvoiceResource($this->invoice);
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return new PaymentMethodResource($this->paymentMethod);
            }),
            'fields' => $this->whenLoaded('fields', function () {
                return CustomFieldValueResource::collection($this->fields);
            }),
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return new CurrencyResource($this->currency);
            }),
            'transaction' => $this->whenLoaded('transaction', function () {
                return new TransactionResource($this->transaction);
            }),
        ];
    }
}
