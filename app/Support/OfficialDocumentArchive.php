<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class OfficialDocumentArchive
{
    public static function archivePdf($model, string $collectionName, string $fileName, string $contents): void
    {
        self::write(
            self::folderFor($model, $collectionName),
            self::sanitizeFileName($fileName, 'pdf'),
            $contents
        );
    }

    public static function archiveExpenseReceipt(string $sourcePath, string $fileName): void
    {
        if (! is_file($sourcePath)) {
            return;
        }

        self::write(
            'EXPENSE',
            self::sanitizeFileName(pathinfo($fileName, PATHINFO_FILENAME), pathinfo($fileName, PATHINFO_EXTENSION)),
            file_get_contents($sourcePath)
        );
    }

    private static function write(string $folder, string $fileName, string $contents): void
    {
        try {
            $directory = self::basePath().DIRECTORY_SEPARATOR.$folder;

            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            file_put_contents($directory.DIRECTORY_SEPARATOR.$fileName, $contents);
        } catch (\Throwable $e) {
            Log::warning('Unable to archive official document.', [
                'folder' => $folder,
                'file_name' => $fileName,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private static function folderFor($model, string $collectionName): string
    {
        if ($collectionName === 'estimate') {
            return 'QUOTATION';
        }

        if ($collectionName === 'payment') {
            return 'PAYMENT';
        }

        if ($collectionName === 'invoice' && $model instanceof Invoice && $model->template_name === Invoice::TEMPLATE_LR_RECEIPT) {
            return 'LR';
        }

        return 'INVOICE';
    }

    private static function basePath(): string
    {
        return env('OFFICIAL_DOCUMENT_ARCHIVE_PATH', 'C:\\DONOTTOUCH\\IMPDOCUMENT');
    }

    private static function sanitizeFileName(string $fileName, string $extension): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $fileName);
        $name = trim((string) $name, " .\t\n\r\0\x0B");

        if ($name === '') {
            $name = 'document';
        }

        $extension = trim($extension, '. ');

        return mb_substr($name, 0, 180).($extension ? '.'.$extension : '');
    }
}
