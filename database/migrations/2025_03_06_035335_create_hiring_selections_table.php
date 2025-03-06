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
        Schema::create('hiring_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiring_request_id')->constrained('hiring_requests')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users'); // Assuming users table stores employees
            $table->text('selection_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hiring_selections');
    }
};
