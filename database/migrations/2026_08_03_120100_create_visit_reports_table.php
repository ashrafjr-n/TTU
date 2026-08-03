<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_reports', function (Blueprint $table) {
            $table->id();
            // حجز واحد ياخذ تقرير زيارة واحد بس (unique) — Booking hasOne VisitReport
            $table->foreignId('booking_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users');
            $table->text('condition'); // حالة المريض عند الزيارة
            $table->text('examination'); // ملاحظات الفحص
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_reports');
    }
};
