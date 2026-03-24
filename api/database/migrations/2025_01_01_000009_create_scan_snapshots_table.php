<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('passed_count');
            $table->unsignedSmallInteger('failed_count');
            $table->unsignedSmallInteger('total_checks');
            $table->timestamps();

            $table->index(['site_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_snapshots');
    }
};

