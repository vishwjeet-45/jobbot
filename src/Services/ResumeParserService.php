<?php

namespace Ddt\JobBot\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ResumeParserService
{
    /**
     * Extract raw text from uploaded resume file.
     *
     * Returns ['raw_text' => '...'] or null on failure.
     */
    public function parse(UploadedFile $file): ?array
    {
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $text = match ($ext) {
            'pdf'         => $this->parsePdf($path),
            'doc', 'docx' => $this->parseDoc($path),
            default       => null,
        };

        if (!$text || !trim($text)) {
            return null;
        }

        return ['raw_text' => $text];
    }

    private function parsePdf(string $path): ?string
    {
        try {
            $output = shell_exec('pdftotext ' . escapeshellarg($path) . ' -');
            return $output ?: null;
        } catch (\Throwable $e) {
            Log::error('JobBot: PDF parse failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function parseDoc(string $path): ?string
    {
        try {
            $output = shell_exec('antiword ' . escapeshellarg($path));
            return $output ?: null;
        } catch (\Throwable $e) {
            Log::error('JobBot: DOC parse failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
