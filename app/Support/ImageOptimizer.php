<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Kompres gambar unggahan ke WebP (PRD: gambar dikompres WebP) memakai GD bawaan PHP.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1600;

    public const QUALITY = 80;

    public function storeAsWebp(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('File bukan gambar yang valid.');
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width > self::MAX_WIDTH) {
            $newHeight = (int) round($height * self::MAX_WIDTH / $width);
            $resized = imagecreatetruecolor(self::MAX_WIDTH, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, self::MAX_WIDTH, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        } else {
            imagepalettetotruecolor($source);
            imagesavealpha($source, true);
        }

        ob_start();
        imagewebp($source, null, self::QUALITY);
        $binary = (string) ob_get_clean();
        imagedestroy($source);

        $path = trim($directory, '/').'/'.Str::ulid()->toBase32().'.webp';
        Storage::disk($disk)->put($path, $binary, 'public');

        return $path;
    }
}
