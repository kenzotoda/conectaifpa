<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SupabaseObjectClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.supabase.url')) && filled(config('services.supabase.service_role'));
    }

    public function objectApiUrl(string $bucket, string $objectPath): string
    {
        $base = rtrim((string) config('services.supabase.url'), '/');
        $objectPath = ltrim($objectPath, '/');

        return "{$base}/storage/v1/object/{$bucket}/{$objectPath}";
    }

    public function headers(): array
    {
        $key = config('services.supabase.service_role');

        return [
            'Authorization' => 'Bearer '.$key,
            'apikey' => $key,
        ];
    }

    /**
     * @throws \RuntimeException
     */
    public function upload(string $bucket, string $objectPath, string $binary, string $contentType, bool $upsert = true): void
    {
        $httpHeaders = $this->headers();
        $httpHeaders['Content-Type'] = $contentType;
        if ($upsert) {
            $httpHeaders['x-upsert'] = 'true';
        }

        $response = Http::withHeaders($httpHeaders)
            ->withBody($binary, $contentType)
            ->post($this->objectApiUrl($bucket, $objectPath));

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Falha ao enviar objeto ao Supabase: '.$response->body()
            );
        }
    }

    public function fetch(string $bucket, string $objectPath): ?Response
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $objectPath = ltrim($objectPath, '/');

        return Http::withHeaders($this->headers())
            ->get($this->objectApiUrl($bucket, $objectPath));
    }

    /**
     * Remove objeto do bucket. Ignora resposta 404 (já removido ou inexistente).
     */
    public function deleteObject(string $bucket, string $objectPath): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $objectPath = ltrim($objectPath, '/');

        Http::withHeaders($this->headers())
            ->delete($this->objectApiUrl($bucket, $objectPath));
    }
}
