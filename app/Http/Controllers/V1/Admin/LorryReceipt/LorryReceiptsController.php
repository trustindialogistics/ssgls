<?php

namespace App\Http\Controllers\V1\Admin\LorryReceipt;

use App\Http\Controllers\Controller;
use App\Http\Requests\LorryReceiptRequest;
use App\Http\Resources\LorryReceiptResource;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class LorryReceiptsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $limit = $request->input('limit', 10);

        $query = LorryReceipt::query()
            ->where('company_id', $request->header('company'))
            ->with(['ownerCustomer', 'driverCustomer', 'brokerCustomer'])
            ->latest();

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($query) use ($search) {
                $query
                    ->where('contract_no', 'LIKE', $search)
                    ->orWhere('challan_no', 'LIKE', $search)
                    ->orWhere('lorry_no', 'LIKE', $search)
                    ->orWhere('from_name', 'LIKE', $search)
                    ->orWhere('to_name', 'LIKE', $search);
            });
        }

        $receipts = $limit === 'all' ? $query->get() : $query->paginate($limit);

        return LorryReceiptResource::collection($receipts)->response();
    }

    public function store(LorryReceiptRequest $request): LorryReceiptResource
    {
        $this->authorize('create', Invoice::class);

        $payload = Arr::only($request->all(), LorryReceipt::PAYLOAD_FIELDS);
        $payload['company_id'] = (int) $request->header('company');

        return new LorryReceiptResource(LorryReceipt::createFromPayload($payload));
    }

    public function show(Request $request, LorryReceipt $lorryReceipt): LorryReceiptResource
    {
        $this->authorize('view', Invoice::class);

        abort_unless((int) $lorryReceipt->company_id === (int) $request->header('company'), 404);

        return new LorryReceiptResource($lorryReceipt->load(['ownerCustomer', 'driverCustomer', 'brokerCustomer']));
    }

    public function update(LorryReceiptRequest $request, LorryReceipt $lorryReceipt): LorryReceiptResource
    {
        $this->authorize('update', Invoice::class);

        abort_unless((int) $lorryReceipt->company_id === (int) $request->header('company'), 404);

        $payload = Arr::only($request->all(), LorryReceipt::PAYLOAD_FIELDS);

        return new LorryReceiptResource($lorryReceipt->updateFromPayload($payload));
    }

    public function destroy(Request $request, LorryReceipt $lorryReceipt): JsonResponse
    {
        $this->authorize('delete', Invoice::class);

        abort_unless((int) $lorryReceipt->company_id === (int) $request->header('company'), 404);

        $lorryReceipt->delete();

        return response()->json(['success' => true]);
    }
}
