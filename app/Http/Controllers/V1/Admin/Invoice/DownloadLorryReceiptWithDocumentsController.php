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
            $tempFiles = [];

            // Add LR Receipt PDF
            $lrPdfPath = storage_path('app/invoices/' . $invoice->id . '.pdf');
            if (file_exists($lrPdfPath)) {
                $merger->addFile($lrPdfPath);
            }

            // Add party document PDFs or converted images
            foreach ($documents as $doc) {
                $path = $doc['path'];
                // Resolve storage path
                $relativePath = ltrim($path, '/');
                if (str_starts_with($relativePath, 'storage/')) {
                    $relativePath = 'public/' . substr($relativePath, 8);
                }
                $fullPath = storage_path('app/' . $relativePath);

                if (!file_exists($fullPath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                if ($extension === 'pdf') {
                    $merger->addFile($fullPath);
                } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $convertedPdf = $this->imageToPdf($fullPath);
                    if ($convertedPdf && file_exists($convertedPdf)) {
                        $merger->addFile($convertedPdf);
                        $tempFiles[] = $convertedPdf;
                    }
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
            } finally {
                // Clean up any temporary files created
                foreach ($tempFiles as $tempFile) {
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                }
            }
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Convert an image to a temporary PDF file.
     */
    private function imageToPdf(string $imagePath): ?string
    {
        if (!file_exists($imagePath)) {
            return null;
        }

        try {
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $imageData = base64_encode(file_get_contents($imagePath));
            
            // Render basic HTML with the image scaled to fit page
            $html = '
            <html>
            <head>
                <style>
                    @page { margin: 0px; }
                    body { margin: 0px; padding: 0px; background-color: #ffffff; text-align: center; }
                    img { max-width: 100%; max-height: 100%; object-fit: contain; }
                </style>
            </head>
            <body>
                <img src="data:image/' . $extension . ';base64,' . $imageData . '" />
            </body>
            </html>';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            
            $tempFile = tempnam(sys_get_temp_dir(), 'img_pdf_');
            rename($tempFile, $tempFile . '.pdf');
            $tempFile = $tempFile . '.pdf';

            file_put_contents($tempFile, $pdf->output());

            return $tempFile;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to convert image to PDF: ' . $e->getMessage());
            return null;
        }
    }
}
