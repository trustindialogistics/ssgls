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

        $dictionary = [
            'UMBERGAON' => 'UMB',
            'UMBARGAON' => 'UMB',
            'VAPI' => 'VAPI',
            'SURAT' => 'SURAT',
            'MUMBAI' => 'MUM',
            'DAMAN' => 'DAM',
            'SILVASSA' => 'SIL',
            'AHMEDABAD' => 'AMD',
        ];

        if (isset($dictionary[$cityName])) {
            $abbrev = $dictionary[$cityName];
        } elseif (strlen($cityName) <= 4) {
            $abbrev = $cityName;
        } else {
            $abbrev = substr($cityName, 0, 3);
        }

        $type = $request->is('*consignees*') ? Customer::TYPE_CONSIGNEE : Customer::TYPE_CUSTOMER;
        $count = Customer::whereCompany()->where('type', $type)->count();
        $sequence = 101 + $count;

        $suggestedCode = $sequence . $abbrev;

        return response()->json([
            'code' => $suggestedCode,
        ]);
    }
}
