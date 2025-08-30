<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ObjectUploadService
{
    public function __construct(private ?FilesystemFactory $filesystem = null)
    {
        // If not injected, resolve from the container so `new` works
        $this->filesystem ??= app('filesystem');
    }

    /**
     * Upload an object to storage (S3 by default).
     *
     * @param  string                     $baseDirectory  e.g. 'selfies', 'meetings/shop_photos', 'audio/notes'
     * @param  \Illuminate\Http\UploadedFile|string $file  UploadedFile or local path string
     * @param  int|string                 $userId
     * @param  string                     $prefix         e.g. 'start', 'meeting_start', 'voice'
     * @param  array<string,mixed>        $options        [
     *   'disk' => 's3',
     *   'visibility' => 'private'|'public',
     *   'add_date_path' => true,            // appends Y/m/d
     *   'append_user_path' => true,         // appends {userId}
     *   'filename' => null,                 // force a specific filename (no ext auto)
     *   'extension' => null,                // override ext (otherwise guessed)
     *   'metadata' => [],                   // S3 metadata headers (x-amz-meta-*)
     *   'headers' => [],                    // S3 put headers (e.g. ContentType)
     *   'signed_ttl' => 10,                 // minutes for signed URL when private
     * ]
     *
     * @return array{key:string,url:?string,disk:string,visibility:string,content_type:?string,size:?int}
     *
     * @throws \RuntimeException on failure
     */
    public function upload(
        string $baseDirectory,
        UploadedFile|string $file,
        int|string $userId,
        string $prefix = 'file',
        array $options = []
    ): array {
        $diskName    = $options['disk'] ?? 's3';
        $visibility  = $options['visibility'] ?? 'private'; // default private
        $addDatePath = $options['add_date_path'] ?? true;
        $appendUser  = $options['append_user_path'] ?? true;
        $signedTtl   = (int) ($options['signed_ttl'] ?? 10);

        $disk = $this->filesystem->disk($diskName);

        // Build directory path
        $segments = [$baseDirectory];
        if ($appendUser) $segments[] = (string) $userId;
        if ($addDatePath) $segments[] = now()->format('Y/m/d');
        $directory = trim(implode('/', array_filter($segments)), '/');

        // Resolve filename + extension
        $ext = $options['extension'] ?? $this->guessExtension($file);
        $filename = $options['filename']
            ?? ($prefix . '_' . Str::uuid() . ($ext ? ('.' . $ext) : ''));

        $key = $directory . '/' . $filename;

        // Content-Type & size for logging/headers
        [$contentType, $size] = $this->guessContentTypeAndSize($file);

        $putOptions = Arr::only($options, ['metadata', 'headers']);
        $putOptions['visibility'] = $visibility;

        // Ensure headers if we know content-type
        if (!isset($putOptions['headers']['ContentType']) && $contentType) {
            $putOptions['headers']['ContentType'] = $contentType;
        }

        Log::info('ObjectUploadService: uploading', [
            'disk'        => $diskName,
            'key'         => $key,
            'visibility'  => $visibility,
            'contentType' => $contentType,
            'size'        => $size,
        ]);

        // Do upload
        $success = false;
        if ($file instanceof UploadedFile) {
            // Use putFileAs for UploadedFile (streams + detects mime reliably)
            $success = $disk->putFileAs($directory, $file, $filename, $putOptions);
        } else {
            // $file is a local path or raw binary string
            if (is_string($file) && is_file($file)) {
                $payload = fopen($file, 'r');
                $success = $disk->put($key, $payload, $putOptions);
                if (is_resource($payload)) fclose($payload);
            } else {
                // treat as raw contents
                $success = $disk->put($key, $file, $putOptions);
            }
        }

        if (!$success) {
            Log::error('ObjectUploadService: put failed', ['key' => $key]);
            throw new \RuntimeException('Upload failed');
        }

        // Verify existence
        if (!$disk->exists($key)) {
            Log::error('ObjectUploadService: existence check failed', ['key' => $key]);
            throw new \RuntimeException('Upload verification failed');
        }

        // URL generation
        $url = null;
        try {
            if ($visibility === 'public') {
                $url = $disk->url($key);
            } else {
                // Private: signed URL if supported (S3)
                if (method_exists($disk, 'temporaryUrl')) {
                    $url = $disk->temporaryUrl($key, now()->addMinutes($signedTtl));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ObjectUploadService: URL generation failed', [
                'key' => $key, 'error' => $e->getMessage()
            ]);
        }

        return [
            'key'          => $key,
            'url'          => $url,
            'disk'         => $diskName,
            'visibility'   => $visibility,
            'content_type' => $contentType,
            'size'         => $size,
        ];
    }

    private function guessExtension(UploadedFile|string $file): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalExtension() ?: $file->extension();
        }
        if (is_string($file) && is_file($file)) {
            // naive guess from filename
            return pathinfo($file, PATHINFO_EXTENSION) ?: null;
        }
        return null;
    }

    private function guessContentTypeAndSize(UploadedFile|string $file): array
    {
        if ($file instanceof UploadedFile) {
            return [$file->getMimeType(), $file->getSize()];
        }
        if (is_string($file) && is_file($file)) {
            return [mime_content_type($file) ?: null, filesize($file) ?: null];
        }
        // raw string: unknown size/type
        return [null, is_string($file) ? strlen($file) : null];
    }
}
