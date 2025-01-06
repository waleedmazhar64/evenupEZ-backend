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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name')->nullable();
            $table->string('phone')->nulllable();
            $table->string('profile_img')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->enum('email_notification', ['Yes', 'No'])->default('No');
            $table->enum('sms_notification', ['Yes', 'No'])->default('No');
            $table->enum('push_notification', ['Yes', 'No'])->default('No');
            $table->string('notification_frequency')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
