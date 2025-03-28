<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User relation
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('preferred_job_title')->nullable();
            $table->boolean('is_other_preferred_job_title')->nullable()->default(false);
            $table->text('company_name')->nullable();
            $table->text('description')->nullable();
            $table->string('preferred_work_state')->nullable();
            $table->string('preferred_work_zipcode')->nullable();
            $table->string('years_of_experience_in_the_industry')->nullable();
            $table->enum('job_by', ['INDIVIDUAL', 'COMPANY'])->nullable();
            $table->boolean('activation_payment_made')->default(false);
            $table->boolean('activation_payment_cancel')->default(false);
            $table->text('your_experience')->nullable();
            $table->boolean('familiar_with_safety_protocols')->nullable()->default(false);
            $table->string('resume')->nullable();
            $table->string('status')->default('inactive'); // Add status
            $table->string('step')->nullable(); // Add step
            $table->enum('profile_type', ['EMPLOYER', 'EMPLOYEE'])->default('EMPLOYER');
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
        Schema::dropIfExists('profiles');
    }
}
