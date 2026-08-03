<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * نموذج الحجز المباشر الجديد يلغي بالكامل تدفق "طلب حجز ينتظر موافقة الدكتور".
     */
    public function up(): void
    {
        Schema::dropIfExists('booking_requests');
    }

    public function down(): void
    {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('booking_date');
            $table->unsignedTinyInteger('booking_hour');
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }
};
