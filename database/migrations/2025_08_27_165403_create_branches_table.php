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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Branch code (e.g., CFE001)
            $table->string('name'); // Branch name
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8); // GPS coordinates for geofence
            $table->decimal('longitude', 11, 8);
            $table->integer('radius')->default(100); // Geofence radius in meters
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('operating_hours')->nullable(); // Store daily operating hours
            $table->json('settings')->nullable(); // Branch-specific settings
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
        Schema::dropIfExists('branches');
    }
};
