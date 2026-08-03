<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            // صف واحد بس لكل دكتور — جدول أيام العمل يخزن أرقام أيام
            // الأسبوع (0 = الأحد ... 6 = السبت، بمعيار Carbon dayOfWeek)
            $table->foreignId('doctor_id')->unique()->constrained('users')->onDelete('cascade');
            $table->json('working_days');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
