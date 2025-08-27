<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Cuti Tahunan", "Sakit", "Izin"
            $table->string('code')->unique(); // e.g., "ANNUAL", "SICK", "PERMISSION"
            $table->text('description')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_document')->default(false); // e.g., sick leave needs doctor's note
            $table->integer('max_days_per_year')->nullable(); // Annual quota
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Additional leave type settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
};
