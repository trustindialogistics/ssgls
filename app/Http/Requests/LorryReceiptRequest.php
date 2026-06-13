<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LorryReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'driver_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'broker_customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'contract_no' => ['nullable', 'string', 'max:255'],
            'challan_no' => ['required', 'string', 'max:255'],
            'from_code' => ['nullable', 'string', 'max:50'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'to_code' => ['nullable', 'string', 'max:50'],
            'to_name' => ['nullable', 'string', 'max:255'],
            'no_of_pages' => ['nullable', 'integer'],
            'no_of_pkgs' => ['nullable', 'integer'],
            'actual_weight' => ['nullable', 'numeric'],
            'charge_weight' => ['nullable', 'numeric'],
            'lorry_no' => ['nullable', 'string', 'max:50'],
            'rate' => ['nullable', 'numeric'],
            'distance_kms' => ['nullable', 'numeric'],
            'received_no_bilties' => ['nullable', 'string', 'max:1000', 'regex:/^[0-9,]*$/'],
            'advance_amount' => ['nullable', 'numeric'],
            'advance_on' => ['nullable', 'date'],
            'advance_bank' => ['nullable', 'string', 'max:255'],
            'advance_cash_cheque_no' => ['nullable', 'string', 'max:255'],
            'net_amount_payable' => ['nullable', 'numeric'],
            'final_balance_on' => ['nullable', 'date'],
            'final_balance_paid_at' => ['nullable', 'string', 'max:255'],
            'final_balance_code' => ['nullable', 'string', 'max:50'],
            'final_cash_cheque_no' => ['nullable', 'string', 'max:255'],
            'final_bank' => ['nullable', 'string', 'max:255'],
            'detention_amount' => ['nullable', 'numeric'],
            'extra_hire_amount' => ['nullable', 'numeric'],
            'final_other_amount' => ['nullable', 'numeric'],
            'less_advance_other_branch_amount' => ['nullable', 'numeric'],
            'less_deduction_claims_amount' => ['nullable', 'numeric'],
            'balance_amount' => ['nullable', 'numeric'],
            'balance_payable_at' => ['nullable', 'string', 'max:255'],
            'gross_hire_amount' => ['nullable', 'numeric'],
            'lorry_hire_amount' => ['nullable', 'numeric'],
            'other_charges_amount' => ['nullable', 'numeric'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_address' => ['nullable', 'string'],
            'owner_phone' => ['nullable', 'string', 'max:50'],
            'owner_bank_account_no' => ['nullable', 'string', 'max:100'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_address' => ['nullable', 'string'],
            'driver_licence_no' => ['nullable', 'string', 'max:100'],
            'broker_name' => ['nullable', 'string', 'max:255'],
            'broker_address' => ['nullable', 'string'],
            'broker_phone' => ['nullable', 'string', 'max:50'],
            'broker_bank_account_no' => ['nullable', 'string', 'max:100'],
            'paid_to' => ['nullable', 'string', 'max:255'],
            'final_paid_to' => ['nullable', 'string', 'max:255'],
            'financer_name' => ['nullable', 'string', 'max:255'],
            'financer_address' => ['nullable', 'string'],
            
            // Remaining PAYLOAD_FIELDS with appropriate rules
            'regd_at' => ['nullable', 'string'],
            'body_type' => ['nullable', 'string', 'max:255'],
            'make' => ['nullable', 'string', 'max:255'],
            'vehicle_model' => ['nullable', 'string', 'max:255'],
            'colour' => ['nullable', 'string', 'max:50'],
            'chasis_no' => ['nullable', 'string', 'max:255'],
            'engine_no' => ['nullable', 'string', 'max:255'],
            'fitness_validity' => ['nullable', 'date'],
            'road_permit_no' => ['nullable', 'string', 'max:255'],
            'permit_date' => ['nullable', 'date'],
            'permit_valid_in' => ['nullable', 'string', 'max:255'],
            'permit_status_upto' => ['nullable', 'date'],
            'insured_with' => ['nullable', 'string'],
            'insurance_division_no' => ['nullable', 'string', 'max:255'],
            'insurance_certificate_no' => ['nullable', 'string', 'max:255'],
            'insurance_valid_upto' => ['nullable', 'date'],
            'owner_code' => ['nullable', 'string', 'max:255'],
            'driver_place' => ['nullable', 'string', 'max:255'],
            'driver_licence_date' => ['nullable', 'date'],
            'driver_licence_issued_by' => ['nullable', 'string'],
            'driver_rto_address' => ['nullable', 'string'],
            'driver_valid_up_to' => ['nullable', 'date'],
            'advice_no' => ['nullable', 'string', 'max:255'],
            'advice_date' => ['nullable', 'date'],
            'destination_broker_name' => ['nullable', 'string', 'max:255'],
            'destination_broker_address' => ['nullable', 'string'],
            'driver_bank_account_no' => ['nullable', 'string', 'max:100'],
            'gross_hire_rupees' => ['nullable', 'numeric'],
            'balance_payable_code' => ['nullable', 'string', 'max:50'],
            'balance_rupees' => ['nullable', 'numeric'],
            'balance_rupees_only' => ['nullable', 'string', 'max:1000'],
            'hire_passed_by' => ['nullable', 'string', 'max:255'],
            'hire_certified_by' => ['nullable', 'string', 'max:255'],
            'hire_prepared_by' => ['nullable', 'string', 'max:255'],
            'advance_received_by' => ['nullable', 'string', 'max:255'],
            'loading_remarks' => ['nullable', 'string'],
            'loaded_by' => ['nullable', 'string', 'max:255'],
            'final_total_extra_amount' => ['nullable', 'numeric'],
            'grand_total_amount' => ['nullable', 'numeric'],
            'total_less_amount' => ['nullable', 'numeric'],
            'final_cash_cheque_on' => ['nullable', 'date'],
            'final_rupees_only' => ['nullable', 'string', 'max:1000'],
            'final_passed_by' => ['nullable', 'string', 'max:255'],
            'final_certified_by' => ['nullable', 'string', 'max:255'],
            'final_prepared_by' => ['nullable', 'string', 'max:255'],
            'final_payment_received_by' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'received_no_bilties.regex' => 'Received No. of Bilties must contain only numbers and commas (e.g., 101,102,103)',
            'challan_no.required' => 'Challan Number is required',
        ];
    }
}
