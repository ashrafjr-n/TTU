<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_report_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('medication_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['visit_report_id', 'medication_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_report_medications');
    }
};
