<?php

namespace App\Http\Resources;

use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'vat_id' => $this->vat_id,
            'tax_id' => $this->tax_id,
            'gstin' => $this->gstin,
            'enrollment_no' => $this->enrollment_no,
            'pan_no' => $this->pan_no,
            'tagline' => $this->tagline,
            'top_heading' => $this->top_heading,
            'billing_branch_name_address' => $this->billing_branch_name_address,
            'notification_email' => CompanySetting::getSetting('notification_email', $this->id),
            'logo' => $this->logo,
            'logo_path' => $this->logo_path,
            'unique_hash' => $this->unique_hash,
            'owner_id' => $this->owner_id,
            'slug' => $this->slug,
            'address' => $this->whenLoaded('address', function () {
                return new AddressResource($this->address);
            }),
            'roles' => RoleResource::collection($this->roles),
        ];
    }
}
