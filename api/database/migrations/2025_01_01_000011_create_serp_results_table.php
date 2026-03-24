<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serp_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');                    // e.g. "Lift Engineer in Leeds"
            $table->unsignedSmallInteger('position')->nullable(); // 1-100, null = not found
            $table->string('result_url')->nullable();     // URL that appeared in results
            $table->text('snippet')->nullable();           // Google snippet text
            $table->unsignedSmallInteger('total_results')->nullable(); // how many results checked
            $table->timestamps();

            $table->index(['site_id', 'keyword', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serp_results');
    }
};

