<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cloudinary_api_key_id')->constrained('cloudinary_api_keys')->cascadeOnDelete();
            $table->string('name');
            $table->string('public_id');
            $table->string('secure_url');
            $table->string('resource_type')->default('image');
            $table->string('format')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->string('asset_id')->nullable();
            $table->string('folder')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('cloudinary_api_key_id');
            $table->index('public_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
