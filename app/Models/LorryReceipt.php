<?php

namespace App\Models;

use App\Facades\Hashids;
use App\Facades\PDF;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LorryReceipt extends Model
{
    protected $guarded = ['id'];

    public const PAYLOAD_FIELDS = [
        'company_id',
        'owner_customer_id',
        'driver_customer_id',
        'broker_customer_id',
        'contract_no',
        'from_code',
        'from_name',
        'to_code',
        'to_name',
        'challan_no',
        'no_of_pages',
        'no_of_pkgs',
        'actual_weight',
        'charge_weight',
        'lorry_no',
        'rate',
        'distance_kms',
        'regd_at',
        'body_type',
        'make',
        'vehicle_model',
        'colour',
        'chasis_no',
        'engine_no',
        'fitness_validity',
        'road_permit_no',
        'permit_date',
        'permit_valid_in',
        'permit_status_upto',
        'insured_with',
        'insurance_division_no',
        'insurance_certificate_no',
        'insurance_valid_upto',
        'owner_code',
        'owner_name',
        'owner_address',
        'owner_phone',
        'financer_name',
        'financer_address',
        'driver_name',
        'driver_address',
        'driver_place',
        'driver_licence_no',
        'driver_licence_date',
        'driver_licence_issued_by',
        'driver_rto_address',
        'driver_valid_up_to',
        'broker_name',
        'broker_address',
        'advice_no',
        'advice_date',
        'destination_broker_name',
        'destination_broker_address',
        'broker_phone',
        'paid_to',
        'lorry_hire_amount',
        'other_charges_amount',
        'gross_hire_rupees',
        'gross_hire_amount',
        'advance_cash_cheque_no',
        'advance_on',
        'advance_bank',
        'advance_amount',
        'balance_payable_at',
        'balance_payable_code',
        'balance_rupees',
        'balance_amount',
        'balance_rupees_only',
        'hire_passed_by',
        'hire_certified_by',
        'hire_prepared_by',
        'advance_received_by',
        'loading_remarks',
        'loaded_by',
        'final_paid_to',
        'detention_amount',
        'extra_hire_amount',
        'final_other_amount',
        'final_total_extra_amount',
        'grand_total_amount',
        'less_advance_other_branch_amount',
        'less_deduction_claims_amount',
        'total_less_amount',
        'final_balance_paid_at',
        'final_balance_code',
        'final_balance_on',
        'net_amount_payable',
        'final_cash_cheque_no',
        'final_cash_cheque_on',
        'final_bank',
        'final_rupees_only',
        'final_passed_by',
        'final_certified_by',
        'final_prepared_by',
        'final_payment_received_by',
        'received_no_bilties',
    ];

    protected $appends = [
        'pdf_url',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ownerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id');
    }

    public function driverCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'driver_customer_id');
    }

    public function brokerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'broker_customer_id');
    }

    public function getPdfUrlAttribute(): string
    {
        return url('/lorry-receipts/pdf/'.$this->unique_hash);
    }

    public static function createFromPayload(array $payload): self
    {
        $receipt = self::create($payload);
        $receipt->unique_hash = Hashids::connection(self::class)->encode($receipt->id);
        $receipt->save();

        return $receipt->fresh(['ownerCustomer', 'driverCustomer', 'brokerCustomer', 'company']);
    }

    public function updateFromPayload(array $payload): self
    {
        $this->update($payload);

        return $this->fresh(['ownerCustomer', 'driverCustomer', 'brokerCustomer', 'company']);
    }

    public function getPDFData()
    {
        $receipt = $this->loadMissing(['ownerCustomer', 'driverCustomer', 'brokerCustomer', 'company']);
        $company = Company::find($receipt->company_id);
        $fieldMap = [
            'From' => 'from_name',
            'To' => 'to_name',
            'No Of Pages' => 'no_of_pages',
            'No Of Packages' => 'no_of_pkgs',
            'Actual Weight' => 'actual_weight',
            'Charge Weight' => 'charge_weight',
            'Lorry No' => 'lorry_no',
            'Regd at' => 'regd_at',
            'Body Type' => 'body_type',
            'Make' => 'make',
            'Model' => 'vehicle_model',
            'Colour' => 'colour',
            'Chasis No' => 'chasis_no',
            'Engine No' => 'engine_no',
            'Owner Name' => 'owner_name',
            'Owner Address' => 'owner_address',
            'Owner Phone No' => 'owner_phone',
            'Financer Name' => 'financer_name',
            'Financer Address' => 'financer_address',
            'Driver Name' => 'driver_name',
            'Driver Address' => 'driver_address',
            'Driver Place' => 'driver_place',
            'Driver Licence No' => 'driver_licence_no',
            'Driver Licence Date' => 'driver_licence_date',
            'Driver Licence Issued By' => 'driver_licence_issued_by',
            'Driver RTO' => 'driver_rto_address',
            'Driver Valid Up To' => 'driver_valid_up_to',
            'Broker Name' => 'broker_name',
            'Broker Address' => 'broker_address',
            'Advice No' => 'advice_no',
            'Advice Date' => 'advice_date',
            'Destination Broker Name' => 'destination_broker_name',
            'Destination Broker Address' => 'destination_broker_address',
            'Broker Phone No' => 'broker_phone',
            'Paid To' => 'paid_to',
            'Lorry Hire Amount' => 'lorry_hire_amount',
            'Other Charges Amount' => 'other_charges_amount',
            'Gross Hire Rupees' => 'gross_hire_rupees',
            'Advance Cash Cheque No' => 'advance_cash_cheque_no',
            'Advance On' => 'advance_on',
            'Advance Bank' => 'advance_bank',
            'Advance Amount' => 'advance_amount',
            'Balance Payable At' => 'balance_payable_at',
            'Balance Amount' => 'balance_amount',
            'Balance Rupees Only' => 'balance_rupees_only',
            'Hire Passed By' => 'hire_passed_by',
            'Hire Certified By' => 'hire_certified_by',
            'Hire Prepared By' => 'hire_prepared_by',
            'Advance Received By' => 'advance_received_by',
            'Loaded By' => 'loaded_by',
            'Final Paid To' => 'final_paid_to',
            'Detention Amount' => 'detention_amount',
            'Extra Hire Amount' => 'extra_hire_amount',
            'Final Other Amount' => 'final_other_amount',
            'Final Total Extra Amount' => 'final_total_extra_amount',
            'Grand Total' => 'grand_total_amount',
            'Less Advance Other Branch Amount' => 'less_advance_other_branch_amount',
            'Less Deduction Claims Amount' => 'less_deduction_claims_amount',
            'Total Less Amount' => 'total_less_amount',
            'Final Balance Paid At' => 'final_balance_paid_at',
            'Final Balance Code' => 'final_balance_code',
            'Final Balance Date' => 'final_balance_on',
            'Net Amount Payable' => 'net_amount_payable',
            'Final Cash Cheque No' => 'final_cash_cheque_no',
            'Final Cash Cheque On' => 'final_cash_cheque_on',
            'Final Bank' => 'final_bank',
            'Final Rupees Only' => 'final_rupees_only',
            'Final Passed By' => 'final_passed_by',
            'Final Certified By' => 'final_certified_by',
            'Final Prepared By' => 'final_prepared_by',
            'Final Payment Received By' => 'final_payment_received_by',
            'Received No Of Bilties' => 'received_no_bilties',
        ];
        $fields = collect($fieldMap)->map(function ($attribute, $label) use ($receipt) {
            return (object) [
                'defaultAnswer' => (string) ($receipt->{$attribute} ?? ''),
                'customField' => (object) [
                    'name' => $label,
                    'label' => $label,
                ],
            ];
        })->values();
        $invoice = (object) [
            'invoice_number' => $receipt->challan_no ?: $receipt->contract_no ?: $receipt->id,
            'fields' => $fields,
            'company' => $company,
            'company_id' => $receipt->company_id,
        ];

        view()->share([
            'lorryReceipt' => $receipt,
            'invoice' => $invoice,
            'company_address' => $this->getCompanyAddressLines($company),
            'logo' => $company?->logo_path,
        ]);

        $templatePath = 'app.pdf.invoice.lorry_receipt';

        if (request()->has('preview')) {
            return view($templatePath);
        }

        return PDF::loadView($templatePath);
    }

    private function getCompanyAddressLines(?Company $company): string
    {
        if (! $company) {
            return '';
        }

        $address = $company->address;
        $lines = array_filter([
            $address?->address_street_1,
            $address?->address_street_2,
            trim(implode(' ', array_filter([$address?->city, $address?->state, $address?->zip]))),
            $address?->country_name,
        ]);
        $email = CompanySetting::getSetting('notification_email', $company->id);

        if ($email) {
            $lines[] = 'E-mail : '.$email;
        }

        return implode('<br>', array_map(fn ($line) => e($line), $lines));
    }
}
