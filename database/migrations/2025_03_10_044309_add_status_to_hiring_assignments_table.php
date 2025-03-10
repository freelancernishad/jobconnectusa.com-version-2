<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToHiringAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hiring_assignments', function (Blueprint $table) {
            // Add the status column with a default value
            $table->string('status')->default('Pending')->after('assignment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hiring_assignments', function (Blueprint $table) {
            // Drop the status column if the migration is rolled back
            $table->dropColumn('status');
        });
    }
}
