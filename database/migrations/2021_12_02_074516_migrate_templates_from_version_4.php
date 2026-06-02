<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = Storage::disk('views')->files('/app/pdf/invoice');

        foreach ($templates as $key => $template) {
            $templateName = Str::before(basename($template), '.blade.php');
            $this->copyLegacyTemplatePreview($templateName);
        }

        $templates = Storage::disk('views')->files('/app/pdf/estimate');

        foreach ($templates as $key => $template) {
            $templateName = Str::before(basename($template), '.blade.php');
            $this->copyLegacyTemplatePreview($templateName);
        }
    }

    private function copyLegacyTemplatePreview(string $templateName): void
    {
        $sourcePath = public_path("/assets/img/PDF/{$templateName}.png");
        $buildPath = public_path("/build/img/PDF/{$templateName}.png");
        $resourcePath = resource_path("/static/img/PDF/{$templateName}.png");

        if (file_exists($resourcePath) || ! file_exists($sourcePath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($buildPath));
        File::ensureDirectoryExists(dirname($resourcePath));

        copy($sourcePath, $buildPath);
        copy($sourcePath, $resourcePath);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
