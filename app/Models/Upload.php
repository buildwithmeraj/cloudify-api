<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Upload extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cloudinaryKey(): BelongsTo
    {
        return $this->belongsTo(CloudinaryApiKeys::class, 'cloudinary_api_key_id');
    }
}
