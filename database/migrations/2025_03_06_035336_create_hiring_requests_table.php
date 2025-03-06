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
        Schema::create('hiring_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users'); // Assuming users table stores employer
            $table->string('job_title');
            $table->text('job_description');
            $table->string('expected_start_date')->nullable();
            $table->decimal('salary_offer', 8, 2);
            $table->enum('status', ['Prepaid','Pending', 'Assigned', 'Completed'])->default('Prepaid');
            $table->integer('employee_needed')->default(1);
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->integer('total_hours')->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hiring_requests');
    }
};
