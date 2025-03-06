<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackageIdToCustomPackageRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('custom_package_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('package_id')->nullable()->after('id');
            $table->foreign('package_id')->references('id')->on('packages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_package_requests', function (Blueprint $table) {
            $table->dropForeign(['package_id']); // Drop foreign key constraint
            $table->dropColumn('package_id'); // Drop the column
        });
    }
}
