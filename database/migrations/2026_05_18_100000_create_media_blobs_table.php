<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_blobs', function (Blueprint $table): void {
            $table->id();
            $table->char('hash', 64);
            $table->string('disk', 32);
            $table->string('scope', 32);
            $table->string('scope_key', 255)->default('');
            $table->string('path');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size');
            $table->string('extension', 16)->nullable();
            $table->unsignedInteger('reference_count')->default(0);
            $table->timestamps();

            $table->unique(['hash', 'disk', 'scope', 'scope_key']);
            $table->index(['reference_count', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_blobs');
    }
};
