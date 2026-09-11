<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_uuid')->unique();
            $table->string('fcm_token')->nullable();
            $table->string('asc_code')->nullable();
            $table->string('platform')->nullable(); // android, ios, web
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('asc_code')->references('code_unique')->on('ascs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_devices');
    }
};
