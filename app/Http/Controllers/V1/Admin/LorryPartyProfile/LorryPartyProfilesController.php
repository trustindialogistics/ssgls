<?php

namespace App\Http\Controllers\V1\Admin\LorryPartyProfile;

use App\Http\Controllers\Controller;
use App\Http\Requests\LorryPartyProfileRequest;
use App\Http\Resources\LorryPartyProfileResource;
use App\Models\Invoice;
use App\Models\LorryPartyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Silber\Bouncer\BouncerFacade;

class LorryPartyProfilesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $limit = $request->input('limit', 10);

        $query = LorryPartyProfile::query()
            ->where('company_id', $request->header('company'))
            ->with(['customer.billingAddress', 'customer.shippingAddress']);

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', $search)
                    ->orWhere('code', 'LIKE', $search)
                    ->orWhere('phone', 'LIKE', $search);
            });
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'LIKE', '%'.$request->input('phone').'%');
        }

        $orderByField = $request->input('orderByField', 'created_at');
        $orderBy = $request->input('orderBy', 'desc');

        if (in_array($orderByField, ['name', 'phone', 'created_at'], true)) {
            $query->orderBy($orderByField, $orderBy === 'asc' ? 'asc' : 'desc');
        }

        $profiles = $limit === 'all' ? $query->get() : $query->paginate($limit);

        return LorryPartyProfileResource::collection($profiles)->response();
    }

    public function store(LorryPartyProfileRequest $request): LorryPartyProfileResource
    {
        $this->authorize('create', Invoice::class);

        $payload = $request->validated();
        $payload['company_id'] = (int) $request->header('company');

        $documentFields = [
            'rc_front_path', 'rc_back_path', 'pan_front_path', 'insurance_path',
            'license_front_path', 'license_back_path', 'pan_front_path_broker'
        ];
        foreach ($documentFields as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = $this->handleBase64Upload($payload[$field], $field);
            }
        }

        $profile = LorryPartyProfile::create($payload);
        $this->syncAssociatedCustomer($profile, $payload);

        return new LorryPartyProfileResource($profile->load(['customer.billingAddress', 'customer.shippingAddress']));
    }

    public function show(Request $request, LorryPartyProfile $lorry_party_profile): LorryPartyProfileResource
    {
        $this->authorize('view', Invoice::class);

        abort_unless((int) $lorry_party_profile->company_id === (int) $request->header('company'), 404);

        return new LorryPartyProfileResource($lorry_party_profile->load(['customer.billingAddress', 'customer.shippingAddress']));
    }

    public function update(LorryPartyProfileRequest $request, LorryPartyProfile $lorry_party_profile): LorryPartyProfileResource
    {
        $this->authorize('update', Invoice::class);

        abort_unless((int) $lorry_party_profile->company_id === (int) $request->header('company'), 404);

        $payload = $request->validated();

        $documentFields = [
            'rc_front_path', 'rc_back_path', 'pan_front_path', 'insurance_path',
            'license_front_path', 'license_back_path', 'pan_front_path_broker'
        ];
        foreach ($documentFields as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $this->handleBase64Upload($payload[$field], $field);
            }
        }

        $lorry_party_profile->update($payload);

        $payload['company_id'] = $lorry_party_profile->company_id;
        $payload['type'] = $lorry_party_profile->type;
        $this->syncAssociatedCustomer($lorry_party_profile, $payload);

        return new LorryPartyProfileResource($lorry_party_profile->load(['customer.billingAddress', 'customer.shippingAddress']));
    }

    public function destroy(Request $request, LorryPartyProfile $lorry_party_profile): JsonResponse
    {
        abort_unless(
            BouncerFacade::can('delete-invoice', Invoice::class) || BouncerFacade::can('edit-invoice', Invoice::class),
            403
        );

        abort_unless((int) $lorry_party_profile->company_id === (int) $request->header('company'), 404);

        if ($lorry_party_profile->customer_id) {
            \App\Models\Customer::deleteCustomers([$lorry_party_profile->customer_id]);
        }

        $lorry_party_profile->delete();

        return response()->json(['success' => true]);
    }

    private function syncAssociatedCustomer(LorryPartyProfile $profile, array $payload): void
    {
        $companyId = $payload['company_id'] ?? $profile->company_id;
        $currencyIdSetting = \App\Models\CompanySetting::getSetting('currency', $companyId);
        $currencyId = $currencyIdSetting ? (int) $currencyIdSetting : 17;

        $customerPayload = [
            'company_id' => $companyId,
            'name' => $payload['name'] ?: ($payload['code'] ?: 'Unnamed Profile'),
            'phone' => $payload['phone'] ?? null,
            'type' => $payload['type'] ?? $profile->type,
            'currency_id' => $currencyId,
        ];

        if (! empty($profile->customer_id)) {
            $customer = \App\Models\Customer::find($profile->customer_id);
            if ($customer) {
                $customer->update($customerPayload);
            } else {
                $customer = \App\Models\Customer::create($customerPayload);
                $profile->update(['customer_id' => $customer->id]);
            }
        } else {
            $customer = \App\Models\Customer::create($customerPayload);
            $profile->update(['customer_id' => $customer->id]);
        }

        // Add billing address if address is provided
        if (! empty($payload['address'])) {
            $customer->addresses()->updateOrCreate(
                ['type' => \App\Models\Address::BILLING_TYPE],
                [
                    'company_id' => $companyId,
                    'name' => $customer->name,
                    'address_street_1' => $payload['address'],
                ]
            );
        } else {
            $customer->addresses()->where('type', \App\Models\Address::BILLING_TYPE)->delete();
        }
    }

    /**
     * Decode and save base64 uploaded files.
     */
    private function handleBase64Upload(?string $base64Data, string $fieldName): ?string
    {
        if (empty($base64Data)) {
            return null;
        }

        if (!str_starts_with($base64Data, 'data:')) {
            return $base64Data;
        }

        preg_match('/^data:(\w+\/\w+);base64,(.*)$/', $base64Data, $matches);
        if (count($matches) < 3) {
            return null;
        }

        $mimeType = $matches[1];
        $data = base64_decode($matches[2]);

        $extension = match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin'
        };

        $filename = $fieldName . '_' . uniqid() . '.' . $extension;
        $path = 'profiles/' . $filename;

        Storage::disk('public')->put($path, $data);

        return '/storage/' . $path;
    }
}
