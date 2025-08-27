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
        Schema::table('users', function (Blueprint $table) {
            // Add role column if it doesn't exist (it should from the existing model)
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('employee');
            } else {
                // Change role to string for maximum flexibility
                $table->string('role')->default('employee')->change();
            }
            
            // Add additional fields for the flexible system
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->json('preferences')->nullable()->after('last_login_at'); // User preferences/settings
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'last_login_at', 'preferences']);
        });
    }
};
