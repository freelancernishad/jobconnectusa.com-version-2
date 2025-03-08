<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobPostingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('set null'); // Nullable admin ID
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');   // Nullable user ID
            $table->string('created_by')->default('admin'); // Identifier for who created the job
            $table->string('company_name');           // e.g., Nolimits NYC Home Care
            $table->string('location');               // e.g., New York, USA
            $table->string('position');               // e.g., Home/Health Aide
            $table->enum('employment_type', ['part-time', 'full-time', 'both'])->default('both');
            $table->decimal('hourly_rate_min', 8, 2); // Minimum hourly rate
            $table->decimal('hourly_rate_max', 8, 2); // Maximum hourly rate
            $table->string('website')->nullable();    // e.g., nolimitsnyc.com
            $table->string('company_logo')->nullable(); // URL or path to the logo
            $table->integer('total_positions');       // Total positions available
            $table->integer('positions_filled')->default(0); // Positions already filled
            $table->enum('status', ['open', 'closed'])->default('open'); // Status of the job posting
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
    }
}
