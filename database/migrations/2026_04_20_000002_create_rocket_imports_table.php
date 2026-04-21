<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rocket_imports', function (Blueprint $table): void {
            $table->id();
            $table->string('importer');
            $table->string('file_name');
            $table->string('file_disk');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('batch_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('batch_id');
        });

        Schema::create('rocket_failed_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_id')->constrained('rocket_imports')->cascadeOnDelete();
            $table->json('data');
            $table->text('validation_error');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rocket_failed_import_rows');
        Schema::dropIfExists('rocket_imports');
    }
};
