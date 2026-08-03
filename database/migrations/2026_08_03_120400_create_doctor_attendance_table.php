<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->boolean('is_auto_checkout')->default(false); // تسجيل خروج تلقائي الساعة 4 عصرًا
            $table->timestamps();

            $table->unique(['doctor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_attendance');
    }
};
