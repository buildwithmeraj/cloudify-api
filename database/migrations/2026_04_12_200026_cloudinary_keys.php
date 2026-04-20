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
        Schema::create('cloudinary_api_keys', function (Blueprint $table) {
            $table->id();
            $table->text('user_id')->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('name');
            $table->string('key', 64)->unique();
            $table->string('secret', 64)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
