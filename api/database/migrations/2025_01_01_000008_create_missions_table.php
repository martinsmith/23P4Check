<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('description');
            $table->string('category');         // e.g. 'local_seo', 'content', 'technical', 'tracking'
            $table->string('type');              // 'reactive' (from findings) or 'proactive' (from context)
            $table->unsignedTinyInteger('priority')->default(3); // 1 = highest, 5 = lowest
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        Schema::create('mission_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->unsignedTinyInteger('sort')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_steps');
        Schema::dropIfExists('missions');
    }
};

