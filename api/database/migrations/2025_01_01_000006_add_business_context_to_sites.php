<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('name');
            $table->string('location')->nullable()->after('business_type');
            $table->string('service_area')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'location', 'service_area']);
        });
    }
};

