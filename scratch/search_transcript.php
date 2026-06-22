<?php

$path = '/var/www/html/scratch/transcript.jsonl';
if (!file_exists($path)) {
    echo "Transcript file not found at $path\n";
    exit(1);
}

$handle = fopen($path, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if (isset($data['source']) && $data['source'] === 'USER_EXPLICIT' && isset($data['type']) && $data['type'] === 'USER_INPUT') {
            $content = $data['content'] ?? '';
            if (stripos($content, 'profit') !== false || stripos($content, 'report') !== false || stripos($content, 'loss') !== false || stripos($content, 'show') !== false) {
                echo "Index: " . ($data['step_index'] ?? '') . " | Date: " . ($data['created_at'] ?? '') . "\n";
                echo $content . "\n";
                echo str_repeat("-", 50) . "\n";
            }
        }
    }
    fclose($handle);
}
