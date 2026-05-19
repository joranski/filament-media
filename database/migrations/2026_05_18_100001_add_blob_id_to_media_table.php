<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        Schema::table('media', function (Blueprint $table): void {
            if (! Schema::hasColumn('media', 'blob_id')) {
                $table->foreignId('blob_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('media_blobs')
                    ->restrictOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        Schema::table('media', function (Blueprint $table): void {
            if (Schema::hasColumn('media', 'blob_id')) {
                $table->dropForeign(['blob_id']);
                $table->dropColumn('blob_id');
            }
        });
    }
};
