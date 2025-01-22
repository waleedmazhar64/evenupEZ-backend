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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Expense name
            $table->decimal('amount', 10, 2); // Total amount of the expense
            $table->unsignedBigInteger('paid_by'); // The user who paid
            $table->enum('split_type', ['equal', 'custom']); // Split type
            $table->json('split_options'); // JSON to store split details
            $table->date('due_date')->nullable(); // Due date for payment
            $table->string('payment_frequency')->default('onetime'); // Frequency: onetime, monthly, etc.
            $table->timestamps();

            $table->foreign('paid_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
