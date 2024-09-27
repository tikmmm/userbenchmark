<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInitialTables extends Migration
{
    public function up(): void
    {
        Schema::create('pc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cpu_id')->index();
            $table->unsignedBigInteger('gpu_id')->index();
            $table->unsignedBigInteger('ram_id')->index();
            $table->decimal('cpu_score', 10, 2)->nullable();
            $table->decimal('gpu_score', 10, 2)->nullable();
            $table->decimal('ram_score', 10, 2)->nullable();
            $table->timestamps(); // created_at, updated_at

            // Foreign key constraints
            $table->foreign('cpu_id')->references('id')->on('hardwares')->onDelete('set null');
            $table->foreign('gpu_id')->references('id')->on('hardwares')->onDelete('set null');
            $table->foreign('ram_id')->references('id')->on('hardwares')->onDelete('set null');
        });

        Schema::create('storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pc_id')->nullable()->index();
            $table->unsignedBigInteger('storage_id')->nullable()->index();
            $table->enum('type', ['SSD', 'HDD']);
            $table->decimal('score', 10, 2)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('pc_id')->references('id')->on('pc')->onDelete('cascade');
            $table->foreign('storage_id')->references('id')->on('hardwares')->onDelete('set null');
        });

        Schema::create('parts', function (Blueprint $table) {
            $table->id(); // serial
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('part')->nullable();
            $table->unsignedBigInteger('cpu_id')->nullable(); // Foreign key for CPU
            $table->unsignedBigInteger('gpu_id')->nullable(); // Foreign key for GPU
            $table->unsignedBigInteger('ram_id')->nullable(); // Foreign key for RAM
            $table->unsignedBigInteger('storage_id')->nullable(); // Foreign key for Storage
            $table->decimal('min_score', 10, 2)->nullable();
            $table->decimal('avg_score', 10, 2)->nullable();
            $table->decimal('max_score', 10, 2)->nullable();
            $table->timestamps();

            // Unique constraints
            $table->unique(['brand', 'model', 'part'], 'parts_brand_model_part_unique');
            $table->unique(['cpu_id', 'gpu_id', 'ram_id'], 'unique_cpu_gpu_ram');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pc');
        Schema::dropIfExists('storage');
        Schema::dropIfExists('parts');
    }
}
