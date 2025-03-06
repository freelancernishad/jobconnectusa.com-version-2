<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobAppliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_applies', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key for user
            $table->string('company_name');
            $table->string('service');
            $table->string('location');
            $table->json('employment_type'); // Store as JSON if multiple types
            $table->decimal('hourly_rate_min', 8, 2);
            $table->decimal('hourly_rate_max', 8, 2);
            $table->longText('note')->nullable();
            $table->enum('job_by', ['INDIVIDUAL', 'COMPANY'])->nullable();
            $table->string('position')->nullable();
            $table->integer('total_positions')->nullable();
            $table->string('website')->nullable();
            $table->string('company_logo')->nullable();
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_applies');
    }
}
