<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceTitleToUserLookingServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_looking_services', function (Blueprint $table) {
            // Add the new column
            $table->string('service_title')->nullable()->after('service_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_looking_services', function (Blueprint $table) {
            // Drop the column if the migration is rolled back
            $table->dropColumn('service_title');
        });
    }
}
