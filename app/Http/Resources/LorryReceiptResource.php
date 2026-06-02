<?php

namespace App\Http\Resources;

use App\Models\LorryReceipt;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class LorryReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        return array_merge(
            Arr::only($this->resource->toArray(), array_merge(['id', 'unique_hash', 'created_at', 'updated_at'], LorryReceipt::PAYLOAD_FIELDS)),
            [
                'pdf_url' => $this->pdf_url,
                'owner_customer' => $this->whenLoaded('ownerCustomer', function () {
                    return new CustomerResource($this->ownerCustomer);
                }),
                'driver_customer' => $this->whenLoaded('driverCustomer', function () {
                    return new CustomerResource($this->driverCustomer);
                }),
                'broker_customer' => $this->whenLoaded('brokerCustomer', function () {
                    return new CustomerResource($this->brokerCustomer);
                }),
            ]
        );
    }
}
