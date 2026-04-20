<?php

namespace App\Http\Controllers\Api\cloudinary;

use App\Http\Controllers\Controller;
use App\Models\CloudinaryApiKeys;
use App\Models\PublicApiKeys;
use App\Models\Upload;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudinaryFileController extends Controller
{
    private function configureCloudinary(string $cloudName, string $apiKey, string $apiSecret): void
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => ['secure' => true],
        ]);
    }

    private function getImagesTotalSize(array $images): float
    {
        $totalSize = 0;
        foreach ($images as $image) {
            $totalSize += $image->getSize();
        }
        return $totalSize;
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'name'   => 'required|string',
            'images' => 'required|array',
        ]);

        // authenticate via public key
        $token = $request->bearerToken();
        $checkToken = PublicApiKeys::where('key', $token)->first();

        if (!$checkToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $userId = $checkToken->user_id;

        // get all cloudinary keys for the user
        $cloudinaryKeys = CloudinaryApiKeys::where('user_id', $userId)->get();

        if ($cloudinaryKeys->isEmpty()) {
            return response()->json(['message' => 'No Cloudinary accounts configured.'], 422);
        }

        $images = $request->file('images') ?? [];
        $fileSize = $this->getImagesTotalSize($images);

        // find a cloudinary key with enough storage
        $selectedKey = null;

        foreach ($cloudinaryKeys as $key) {
            if (empty($key->name) || empty($key->key) || empty($key->secret)) {
                continue;
            }

            try {
                $this->configureCloudinary($key->name, $key->key, $key->secret);
                $usage = (new AdminApi())->usage();
            } catch (Throwable $e) {
                Log::warning('Skipping Cloudinary key due to usage check failure', [
                    'cloudinary_key_id' => $key->id,
                    'user_id'           => $userId,
                    'error'             => $e->getMessage(),
                ]);
                continue;
            }

            $storage = $usage['storage'] ?? null;
            if (!$storage) continue;

            $currentUsage = (float) ($storage['usage'] ?? 0);
            $storageLimit = (float) ($storage['limit'] ?? 0);

            // limit=0 means unlimited
            if ($storageLimit === 0.0 || ($currentUsage + $fileSize) < $storageLimit) {
                $selectedKey = $key;
                break;
            }
        }

        if (!$selectedKey) {
            return response()->json([
                'message' => 'No usable Cloudinary account with enough storage was found.',
            ], 507);
        }

        // upload files
        $this->configureCloudinary($selectedKey->name, $selectedKey->key, $selectedKey->secret);
        $uploadApi = new UploadApi();
        $uploadedFiles = [];

        foreach ($images as $image) {
            try {
                $result = $uploadApi->upload($image->getRealPath(), [
                    'folder'        => 'uploads/' . $request->input('name'),
                    'resource_type' => 'auto',
                ]);

                // store in DB
                $savedUpload = Upload::create([
                    'user_id'               => $userId,
                    'cloudinary_api_key_id' => $selectedKey->id,
                    'name'                  => $request->input('name'),
                    'public_id'             => $result['public_id'],
                    'secure_url'            => $result['secure_url'],
                    'resource_type'         => $result['resource_type'] ?? 'image',
                    'format'                => $result['format'] ?? null,
                    'width'                 => $result['width'] ?? null,
                    'height'                => $result['height'] ?? null,
                    'bytes'                 => $result['bytes'] ?? null,
                    'asset_id'              => $result['asset_id'] ?? null,
                    'folder'                => $result['folder'] ?? null,
                    'raw_response'          => is_array($result) ? $result : (method_exists($result, 'getArrayCopy') ? $result->getArrayCopy() : []),
                ]);

                $uploadedFiles[] = [
                    'upload_id'  => $savedUpload->id,
                    'public_id'  => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'format'     => $result['format'] ?? null,
                    'width'      => $result['width'] ?? null,
                    'height'     => $result['height'] ?? null,
                    'bytes'      => $result['bytes'] ?? null,
                    'asset_id'   => $result['asset_id'] ?? null,
                ];
            } catch (Throwable $e) {
                Log::error('Cloudinary upload failed', [
                    'error'   => $e->getMessage(),
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'message' => 'Upload failed for one or more files.',
                    'error'   => $e->getMessage(),
                ], 502);
            }
        }

        return response()->json([
            'message' => 'Upload successful.',
            'files'   => $uploadedFiles,
        ], 201);
    }
}
