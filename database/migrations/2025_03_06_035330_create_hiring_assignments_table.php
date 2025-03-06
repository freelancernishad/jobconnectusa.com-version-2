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
        Schema::create('hiring_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiring_request_id')->constrained('hiring_requests')->onDelete('cascade');
            $table->unsignedBigInteger('admin_id')->nullable();

            // Adding a foreign key constraint to link with the admins table
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->foreignId('assigned_employee_id')->constrained('users'); // Assuming users table stores employees
            $table->text('assignment_note')->nullable();
            $table->date('assignment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hiring_assignments');
    }
};
