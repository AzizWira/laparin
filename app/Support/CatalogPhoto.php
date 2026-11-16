<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class CatalogPhoto
{
    public static function url(string $path): string
    {
        if (str_starts_with($path, 'images/catalog/')) {
            return asset($path);
        }

        return route('catalog.photo', ['filename' => basename($path)]);
    }

    public static function save(?UploadedFile $upload, ?string $previous, Closure $persist): void
    {
        if ($upload === null) {
            $persist($previous);

            return;
        }

        $disk = Storage::disk('public');
        $path = $disk->putFile('catalog', $upload);

        if ($path === false) {
            throw new RuntimeException('Foto katalog gagal disimpan.');
        }

        try {
            $persist($path);
        } catch (Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        if ($previous !== null && str_starts_with($previous, 'catalog/')) {
            $disk->delete($previous);
        }
    }
}
