<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class MediaStorageService
{
    /**
     * Get the default storage disk based on environment
     * Can be overridden by setting FILESYSTEM_DISK in .env
     */
    public static function getDefaultDisk(): string
    {
        return env('FILESYSTEM_DISK', 'public');
    }

    /**
     * Store uploaded file and return media data
     *
     * @param UploadedFile $file
     * @param string $type 'image' or 'video'
     * @param string $directory Base directory (e.g., 'products')
     * @param string|null $subDirectory Optional subdirectory (e.g., product slug)
     * @param string|null $disk Storage disk (null = use default)
     * @return array Media data to save in database
     */
    public static function storeFile(
        UploadedFile $file,
        string $type,
        string $directory = 'products',
        ?string $subDirectory = null,
        ?string $disk = null
    ): array {
        if (!$file->isValid()) {
            throw new Exception('Invalid file uploaded');
        }

        $disk = $disk ?? self::getDefaultDisk();
        $subDirectory = $subDirectory ?: 'general';
        
        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . Str::random(10) . '.' . $extension;
        
        // Build storage path
        $path = $directory . '/' . $subDirectory;
        if ($type === 'video') {
            $path .= '/videos';
        }
        
        // Store file
        $filePath = $file->storeAs($path, $filename, $disk);
        
        // Generate URL
        $url = self::generateUrl($filePath, $disk);
        
        return [
            'file_path' => $filePath,
            'file_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => $disk,
            'url' => $url,
            'type' => $type,
        ];
    }

    /**
     * Store multiple files
     *
     * @param array $files Array of UploadedFile instances
     * @param string $type 'image' or 'video'
     * @param string $directory Base directory
     * @param string|null $subDirectory Optional subdirectory
     * @param string|null $disk Storage disk
     * @return array Array of media data
     */
    public static function storeFiles(
        array $files,
        string $type,
        string $directory = 'products',
        ?string $subDirectory = null,
        ?string $disk = null
    ): array {
        $mediaData = [];
        
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $mediaData[] = self::storeFile($file, $type, $directory, $subDirectory, $disk);
            }
        }
        
        return $mediaData;
    }

    /**
     * Generate URL for a file path based on disk type
     *
     * @param string $filePath
     * @param string $disk
     * @return string
     */
    public static function generateUrl(string $filePath, string $disk): string
    {
        if ($disk === 's3') {
            return Storage::disk('s3')->url($filePath);
        }
        
        // For public disk
        return Storage::disk('public')->url($filePath);
    }

    /**
     * Delete a file from storage
     *
     * @param string $filePath
     * @param string $disk
     * @return bool
     */
    public static function deleteFile(string $filePath, string $disk): bool
    {
        if (empty($filePath)) {
            return false;
        }

        try {
            if (Storage::disk($disk)->exists($filePath)) {
                return Storage::disk($disk)->delete($filePath);
            }
        } catch (Exception $e) {
            // Log error but don't throw
            Log::error("Failed to delete file: {$filePath} from disk: {$disk}", [
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Check if file exists
     *
     * @param string $filePath
     * @param string $disk
     * @return bool
     */
    public static function fileExists(string $filePath, string $disk): bool
    {
        if (empty($filePath)) {
            return false;
        }

        try {
            return Storage::disk($disk)->exists($filePath);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get file URL (regenerate if needed)
     *
     * @param string $filePath
     * @param string $disk
     * @return string|null
     */
    public static function getFileUrl(string $filePath, string $disk): ?string
    {
        if (empty($filePath)) {
            return null;
        }

        try {
            if ($disk === 's3') {
                return Storage::disk('s3')->url($filePath);
            }
            
            return Storage::disk('public')->url($filePath);
        } catch (Exception $e) {
            Log::error("Failed to get file URL: {$filePath} from disk: {$disk}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

