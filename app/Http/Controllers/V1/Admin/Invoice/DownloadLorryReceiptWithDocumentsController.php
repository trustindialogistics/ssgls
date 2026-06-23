<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\LorryPartyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadLorryReceiptWithDocumentsController extends Controller
{
    /**
     * Download Lorry Receipt along with party documents as a single merged PDF.
     */
    public function __invoke(Request $request, int $id): StreamedResponse
    {
        $this->authorize('view', Invoice::class);

        $invoice = Invoice::whereCompany()
            ->with(['customer'])
            ->findOrFail($id);

        // Get party profiles (Owner, Driver, Broker)
        $ownerProfile = $this->getPartyProfile('OWNER', $invoice);
        $driverProfile = $this->getPartyProfile('DRIVER', $invoice);
        $brokerProfile = $this->getPartyProfile('BROKER', $invoice);

        // Collect document paths
        $documents = $this->collectDocuments($ownerProfile, $driverProfile, $brokerProfile);

        return $this->downloadMergedPdf($invoice, $documents);
    }

    /**
     * Get party profile by type.
     */
    private function getPartyProfile(string $type, Invoice $invoice): ?LorryPartyProfile
    {
        $fieldName = ucfirst(strtolower($type)) . ' Name';
        $field = $invoice->fields->firstWhere('label', $fieldName);
        
        if (!$field || !$field->value) {
            return null;
        }

        return LorryPartyProfile::where('type', $type)
            ->where('name', $field->value)
            ->first();
    }

    /**
     * Collect all document paths from profiles.
     */
    private function collectDocuments($ownerProfile, $driverProfile, $brokerProfile): array
    {
        $documents = [];

        if ($ownerProfile) {
            $this->addDoc($documents, $ownerProfile->rc_front_path, 'RC_Front');
            $this->addDoc($documents, $ownerProfile->rc_back_path, 'RC_Back');
            $this->addDoc($documents, $ownerProfile->pan_front_path, 'PAN_Front');
            $this->addDoc($documents, $ownerProfile->insurance_path, 'Insurance_Copy');
        }

        if ($driverProfile) {
            $this->addDoc($documents, $driverProfile->license_front_path, 'License_Front');
            $this->addDoc($documents, $driverProfile->license_back_path, 'License_Back');
        }

        if ($brokerProfile) {
            $this->addDoc($documents, $brokerProfile->pan_front_path_broker, 'PAN_Front');
        }

        return $documents;
    }

    /**
     * Add document to collection if exists.
     */
    private function addDoc(array &$documents, ?string $path, string $label): void
    {
        if ($path) {
            $documents[] = ['label' => $label, 'path' => $path];
        }
    }

    /**
     * Download merged PDF containing LR Receipt and party documents.
     */
    private function downloadMergedPdf(Invoice $invoice, array $documents): StreamedResponse
    {
        $filename = 'LR_' . $invoice->invoice_number . '_with_documents.pdf';

        return response()->streamDownload(function () use ($invoice, $documents) {
            $merger = new \iio\libmergepdf\Merger();

            // Add LR Receipt PDF
            $lrPdfPath = storage_path('app/invoices/' . $invoice->id . '.pdf');
            if (file_exists($lrPdfPath)) {
                $merger->addFile($lrPdfPath);
            }

            // Add party document PDFs
            foreach ($documents as $doc) {
                $fullPath = storage_path('app/' . $doc['path']);
                if (file_exists($fullPath) && strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'pdf') {
                    $merger->addFile($fullPath);
                }
            }

            try {
                echo $merger->merge();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to merge PDFs: ' . $e->getMessage());
                // Fallback: Just send the original LR receipt PDF
                if (file_exists($lrPdfPath)) {
                    readfile($lrPdfPath);
                }
            }
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
