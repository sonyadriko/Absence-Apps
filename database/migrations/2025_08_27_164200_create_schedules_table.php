<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->enum('day', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('break_duration')->default(60); // in minutes
            $table->boolean('is_working_day')->default(true);
            $table->timestamps();
            
            $table->unique(['employee_id', 'day']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedules');
    }
};