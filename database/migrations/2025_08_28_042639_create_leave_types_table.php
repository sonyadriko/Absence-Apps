<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // annual, sick, personal, etc.
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('max_days_per_year')->default(0);
            $table->boolean('carry_forward_allowed')->default(false);
            $table->integer('max_carry_forward_days')->default(0);
            $table->boolean('requires_medical_certificate')->default(false);
            $table->integer('advance_notice_days')->default(0); // Required advance notice in days
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
