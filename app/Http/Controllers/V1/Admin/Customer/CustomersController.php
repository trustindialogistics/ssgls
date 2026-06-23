<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteCustomersRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        if ($request->is('*consignees*')) {
            $request->merge(['type' => 'CONSIGNEE']);
        }

        $limit = $request->has('limit') ? $request->limit : 10;

        $customers = Customer::with(['creator', 'billingAddress'])
            ->whereCompany()
            ->applyFilters($request->all())
            ->withSum([
                'invoices as base_due_amount' => fn ($query) => $query->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE),
            ], 'base_due_amount')
            ->withSum([
                'invoices as due_amount' => fn ($query) => $query->where('template_name', Invoice::TEMPLATE_OFFICE_INVOICE),
            ], 'due_amount')
            ->paginateData($limit);

        $type = $request->get('type', 'CUSTOMER');
        $types = is_array($type) ? $type : explode(',', $type);

        return CustomerResource::collection($customers)
            ->additional(['meta' => [
                'customer_total_count' => Customer::whereCompany()->whereIn('type', $types)->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return CustomerResource
     */
    public function store(Requests\CustomerRequest $request): CustomerResource
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::createCustomer($request);

        return new CustomerResource($customer->load(['billingAddress', 'shippingAddress', 'currency', 'fields.customField']));
    }

    /**
     * Display the specified resource.
     *
     * @return CustomerResource
     */
    public function show(Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer->load(['billingAddress', 'shippingAddress', 'currency', 'fields.customField']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return CustomerResource|JsonResponse
     */
    public function update(Requests\CustomerRequest $request, Customer $customer): CustomerResource|JsonResponse
    {
        $this->authorize('update', $customer);

        $customer = Customer::updateCustomer($request, $customer);

        if (is_string($customer)) {
            return respondJson('you_cannot_edit_currency', 'Cannot change currency once transactions created');
        }

        return new CustomerResource($customer->load(['billingAddress', 'shippingAddress', 'currency', 'fields.customField']));
    }

    /**
     * Remove a list of Customers along side all their resources (ie. Estimates, Invoices, Payments and Addresses)
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function delete(DeleteCustomersRequest $request): JsonResponse
    {
        $this->authorize('delete multiple customers');

        $ids = Customer::whereCompany()
            ->whereIn('id', $request->ids)
            ->pluck('id');

        Customer::deleteCustomers($ids);

        return response()->json([
            'success' => true,
        ]);
    }

    public function suggestCode(Request $request): JsonResponse
    {
        $city = $request->get('city');
        if (empty($city)) {
            return response()->json(['code' => '']);
        }

        $cityName = trim(strtoupper($city));

        // Get city code: first 3 letters by default
        // Use 4 letters if there's potential conflict (cities starting with same 3 letters)
        $cityCode = $this->getCityCode($cityName);

        // Determine customer type and prefix
        $type = $request->is('*consignees*') ? Customer::TYPE_CONSIGNEE : Customer::TYPE_CUSTOMER;
        $typePrefix = $type === Customer::TYPE_CONSIGNEE ? 'CNE' : 'CNR';

        // Get next sequence number for this specific city and type combination
        $lastCustomer = Customer::whereCompany()
            ->where('type', $type)
            ->whereHas('billingAddress', function ($query) use ($cityName) {
                $query->whereRaw('LOWER(city) = ?', [strtolower($cityName)]);
            })
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCustomer) {
            if (preg_match('/' . $cityCode . '(\d{3,})' . $typePrefix . '/', $lastCustomer->prefix, $matches)) {
                $sequence = (int) $matches[1] + 1;
            } elseif (preg_match('/' . $typePrefix . $cityCode . '(\d{3,})/', $lastCustomer->prefix, $matches)) {
                $sequence = (int) $matches[1] + 1;
            } else {
                $sequence = 1;
            }
        } else {
            // Start from 001 for new city+type combinations
            $sequence = 1;
        }

        // Format: CITY + SEQUENCE + TYPE (e.g., SURAT001CNR, VAPI001CNE)
        $suggestedCode = $cityCode . str_pad($sequence, 3, '0', STR_PAD_LEFT) . $typePrefix;

        return response()->json([
            'code' => $suggestedCode,
        ]);
    }

    /**
     * Get city code - 3 letters by default, 4 if conflict detected
     * Dynamically checks database for existing cities with same prefix
     */
    private function getCityCode(string $cityName): string
    {
        // Default: first 3 letters
        $code = substr($cityName, 0, 3);

        // Check database for existing cities with same 3-letter prefix
        // This dynamically detects conflicts without hardcoding
        $existingCities = DB::table('addresses')
            ->select('city')
            ->where('type', 'billing')
            ->whereRaw('UPPER(SUBSTRING(city, 1, 3)) = ?', [strtoupper($code)])
            ->distinct()
            ->pluck('city');

        // Check if there are different cities with same 3-letter prefix
        $hasConflict = false;
        foreach ($existingCities as $existingCity) {
            $existingCityUpper = strtoupper($existingCity);
            $existingPrefix3 = substr($existingCityUpper, 0, 3);
            
            // If same 3-letter prefix but different city name
            if ($existingPrefix3 === $code && strtoupper($cityName) !== $existingCityUpper) {
                $hasConflict = true;
                break;
            }
        }

        // Use 4 letters if conflict detected
        if ($hasConflict && strlen($cityName) >= 4) {
            $code = substr($cityName, 0, 4);
        }

        return $code;
    }
}
