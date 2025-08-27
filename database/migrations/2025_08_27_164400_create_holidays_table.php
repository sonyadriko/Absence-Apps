<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->enum('type', ['national', 'company'])->default('national');
            $table->boolean('is_recurring')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['date', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};