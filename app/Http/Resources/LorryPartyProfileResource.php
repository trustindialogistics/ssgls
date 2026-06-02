<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LorryPartyProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'financer_name' => $this->financer_name,
            'financer_address' => $this->financer_address,
            'place' => $this->place,
            'licence_no' => $this->licence_no,
            'licence_date' => $this->licence_date,
            'licence_issued_by' => $this->licence_issued_by,
            'rto_address' => $this->rto_address,
            'valid_up_to' => $this->valid_up_to,
            'advice_no' => $this->advice_no,
            'advice_date' => $this->advice_date,
            'destination_broker_name' => $this->destination_broker_name,
            'destination_broker_address' => $this->destination_broker_address,
            'created_at' => $this->created_at,
            'formatted_created_at' => optional($this->created_at)->format('d M Y'),
            'customer' => $this->whenLoaded('customer', function () {
                return new CustomerResource($this->customer);
            }),
        ];
    }
}
