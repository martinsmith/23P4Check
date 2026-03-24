<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->json('results'); // { "title": true, "meta_description": false, ... }
            $table->unsignedSmallInteger('passed_count');
            $table->unsignedSmallInteger('failed_count');
            $table->unsignedSmallInteger('total_checks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_scans');
    }
};

