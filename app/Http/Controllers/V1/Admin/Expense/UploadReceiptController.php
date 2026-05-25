<?php

namespace App\Http\Controllers\V1\Admin\Expense;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Http\Requests\UploadExpenseReceiptRequest;
use App\Models\Expense;
use App\Support\OfficialDocumentArchive;
use Illuminate\Http\JsonResponse;

class UploadReceiptController extends Controller
{
    /**
     * Upload the expense receipts to storage.
     *
     * @param  ExpenseRequest  $request
     * @return JsonResponse
     */
    public function __invoke(UploadExpenseReceiptRequest $request, Expense $expense)
    {
        $this->authorize('update', $expense);

        $data = json_decode($request->attachment_receipt);

        if ($data) {
            if ($request->type === 'edit') {
                $expense->clearMediaCollection('receipts');
            }

            $media = $expense->addMediaFromBase64($data->data)
                ->usingFileName($data->name)
                ->toMediaCollection('receipts');

            OfficialDocumentArchive::archiveExpenseReceipt($media->getPath(), $media->file_name);
        }

        return response()->json([
            'success' => 'Expense receipts uploaded successfully',
        ], 200);
    }
}
