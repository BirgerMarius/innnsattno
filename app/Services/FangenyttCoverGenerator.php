<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class FangenyttCoverGenerator
{
    public function generate(string $pdfFile, string $coverFile): array
    {
        $disk = Storage::disk(FangenyttArchive::DISK);

        if (! $disk->exists($pdfFile)) {
            return ['success' => false, 'message' => 'Lokal PDF mangler.'];
        }

        $disk->makeDirectory(dirname($coverFile));
        $disk->delete($coverFile);

        try {
            $process = new Process([
                'pdftoppm',
                '-f', '1',
                '-singlefile',
                '-jpeg',
                '-jpegopt', 'quality=78,progressive=y,optimize=y',
                '-scale-to-x', '240',
                '-scale-to-y', '-1',
                $disk->path($pdfFile),
                pathinfo($disk->path($coverFile), PATHINFO_DIRNAME).'/'.pathinfo($coverFile, PATHINFO_FILENAME),
            ]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful() || ! $disk->exists($coverFile)) {
                $disk->delete($coverFile);

                return ['success' => false, 'message' => 'Kunne ikke generere forside med pdftoppm.'];
            }
        } catch (Throwable $exception) {
            report($exception);

            return ['success' => false, 'message' => 'pdftoppm er ikke tilgjengelig eller forsidegenereringen feilet.'];
        }

        return ['success' => true, 'message' => $coverFile];
    }
}
