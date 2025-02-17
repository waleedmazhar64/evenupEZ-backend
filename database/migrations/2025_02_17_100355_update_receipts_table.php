<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('expense_id'); // Link to user
            $table->text('description')->nullable()->after('file_path'); // Description for each receipt

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

