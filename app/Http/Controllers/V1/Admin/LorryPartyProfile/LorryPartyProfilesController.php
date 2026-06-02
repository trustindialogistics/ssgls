<?php

namespace App\Http\Controllers\V1\Admin\LorryPartyProfile;

use App\Http\Controllers\Controller;
use App\Http\Requests\LorryPartyProfileRequest;
use App\Http\Resources\LorryPartyProfileResource;
use App\Models\Invoice;
use App\Models\LorryPartyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $profile = ! empty($payload['customer_id'])
            ? LorryPartyProfile::updateOrCreate(
                [
                    'company_id' => $payload['company_id'],
                    'customer_id' => $payload['customer_id'],
                    'type' => $payload['type'],
                ],
                $payload
            )
            : LorryPartyProfile::create($payload);

        return new LorryPartyProfileResource($profile->load(['customer.billingAddress', 'customer.shippingAddress']));
    }

    public function show(Request $request, LorryPartyProfile $lorryPartyProfile): LorryPartyProfileResource
    {
        $this->authorize('view', Invoice::class);

        abort_unless((int) $lorryPartyProfile->company_id === (int) $request->header('company'), 404);

        return new LorryPartyProfileResource($lorryPartyProfile->load(['customer.billingAddress', 'customer.shippingAddress']));
    }

    public function update(LorryPartyProfileRequest $request, LorryPartyProfile $lorryPartyProfile): LorryPartyProfileResource
    {
        $this->authorize('update', Invoice::class);

        abort_unless((int) $lorryPartyProfile->company_id === (int) $request->header('company'), 404);

        $lorryPartyProfile->update($request->validated());

        return new LorryPartyProfileResource($lorryPartyProfile->load(['customer.billingAddress', 'customer.shippingAddress']));
    }

    public function destroy(Request $request, LorryPartyProfile $lorryPartyProfile): JsonResponse
    {
        abort_unless(
            BouncerFacade::can('delete-invoice', Invoice::class) || BouncerFacade::can('edit-invoice', Invoice::class),
            403
        );

        abort_unless((int) $lorryPartyProfile->company_id === (int) $request->header('company'), 404);

        $lorryPartyProfile->delete();

        return response()->json(['success' => true]);
    }
}
