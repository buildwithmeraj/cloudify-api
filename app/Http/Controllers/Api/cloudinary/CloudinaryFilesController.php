<?php

namespace App\Http\Controllers\Api\cloudinary;

use App\Http\Controllers\Controller;
use App\Models\CloudinaryApiKeys;
use App\Models\Files;
use App\Models\PublicApiKeys;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudinaryFilesController extends Controller
{
    public function uploadFile(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
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
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $storage = $usage['storage'] ?? null;
            if (!$storage) continue;

            $currentUsage = (float)($storage['usage'] ?? 0);
            $storageLimit = (float)($storage['limit'] ?? 0);

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
                    'folder' => 'uploads/' . $request->input('name'),
                    'resource_type' => 'auto',
                ]);

                // store in DB
                $savedUpload = Files::create([
                    'user_id' => $userId,
                    'cloudinary_api_key_id' => $selectedKey->id,
                    'name' => $request->input('name'),
                    'public_id' => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'resource_type' => $result['resource_type'] ?? 'image',
                    'format' => $result['format'] ?? null,
                    'width' => $result['width'] ?? null,
                    'height' => $result['height'] ?? null,
                    'bytes' => $result['bytes'] ?? null,
                    'asset_id' => $result['asset_id'] ?? null,
                    'folder' => $result['folder'] ?? null,
                    'raw_response' => is_array($result) ? $result : (method_exists($result, 'getArrayCopy') ? $result->getArrayCopy() : []),
                ]);

                $uploadedFiles[] = [
                    'upload_id' => $savedUpload->id,
                    'public_id' => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'format' => $result['format'] ?? null,
                    'width' => $result['width'] ?? null,
                    'height' => $result['height'] ?? null,
                    'bytes' => $result['bytes'] ?? null,
                    'asset_id' => $result['asset_id'] ?? null,
                ];
            } catch (Throwable $e) {
                Log::error('Cloudinary upload failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'message' => 'Upload failed for one or more files.',
                    'error' => $e->getMessage(),
                ], 502);
            }
        }

        return response()->json([
            'message' => 'Upload successful.',
            'files' => $uploadedFiles,
        ], 201);
    }

    private function getImagesTotalSize(array $images): float
    {
        $totalSize = 0;
        foreach ($images as $image) {
            $totalSize += $image->getSize();
        }
        return $totalSize;
    }

    private function configureCloudinary(string $cloudName, string $apiKey, string $apiSecret): void
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => ['secure' => true],
        ]);
    }

    public function listFiles(Request $request)
    {
        // validate request
        $request->validate([
            'name' => 'required|string',
        ]);

        // authenticate via public key
        $token = $request->bearerToken();
        $checkToken = PublicApiKeys::where('key', $token)->first();

        if (!$checkToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        $userId = $checkToken->user_id;

        // get files for the user and name
        $files = Files::where('user_id', $userId)
            ->where('name', $request->input('name'))
            ->get();

        return response()->json([
            'message' => 'Files retrieved successfully.',
            'files' => $files,
        ], 200);
    }

    public function deleteFiles(Request $request)
    {
        // authenticate via public key
        $token = $request->bearerToken();

        // validate request
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'integer|exists:files,id',
            'name' => 'required|string',
        ]);

        // check token
        $checkToken = PublicApiKeys::where('key', $token)->first();

        if (!$checkToken) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        // get files to delete
        $files = $request->input('files');
        if (empty($files) || !is_array($files)) {
            return response()->json(['message' => 'No files specified for deletion.'], 400);
        }

        // ensure all files belong to the user
        $userId = $checkToken->user_id;
        $deletedFiles = [];
        $failedFiles = [];

        // loop through files and delete them
        foreach ($files as $fileId) {
            // find file and check ownership
            $file = Files::where('id', $fileId)->where('user_id', $userId)->where('name', $request->input('name'))->first();

            // if file not found or does not belong to user, skip and log
            if (!$file) {
                $failedFiles[] = ['file_id' => $fileId, 'reason' => 'File not found or does not belong to user.'];
                continue;
            }

            // attempt to delete file from Cloudinary and DB
            try {
                $cloudinaryKey = $file->cloudinaryKey;
                if (!$cloudinaryKey) {
                    $failedFiles[] = ['file_id' => $fileId, 'reason' => 'Cloudinary key not found for this file.'];
                    continue;
                }

                // delete from Cloudinary
                $this->configureCloudinary($cloudinaryKey->name, $cloudinaryKey->key, $cloudinaryKey->secret);
                (new UploadApi())->destroy($file->public_id, ['resource_type' => $file->resource_type]);

                // delete from DB
                $file->delete();
                $deletedFiles[] = ['file_id' => $fileId];
            } catch (Throwable $e) {
                Log::error('Failed to delete file', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'file_id' => $fileId,
                ]);
                $failedFiles[] = ['file_id' => $fileId, 'reason' => 'Deletion failed: ' . $e->getMessage()];
            }
        }

        // return summary of deletion process
        return response()->json([
            'message' => 'File deletion process completed.',
            'deleted_files' => $deletedFiles,
            'failed_files' => $failedFiles,
        ], 200);
    }
}
