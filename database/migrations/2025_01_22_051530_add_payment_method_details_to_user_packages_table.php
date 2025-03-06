<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->string('payment_method_type', 50)->nullable()->after('stripe_customer_id');
            $table->string('card_brand', 50)->nullable()->after('payment_method_type');
            $table->string('card_last_four', 4)->nullable()->after('card_brand');
            $table->integer('card_exp_month')->nullable()->after('card_last_four');
            $table->integer('card_exp_year')->nullable()->after('card_exp_month');
            $table->string('bank_name', 255)->nullable()->after('card_exp_year');
            $table->string('iban_last_four', 4)->nullable()->after('bank_name');
            $table->string('account_holder_type', 50)->nullable()->after('iban_last_four');
            $table->string('account_last_four', 4)->nullable()->after('account_holder_type');
            $table->string('routing_number', 50)->nullable()->after('account_last_four');
        });
    }

    public function down()
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method_type',
                'card_brand',
                'card_last_four',
                'card_exp_month',
                'card_exp_year',
                'bank_name',
                'iban_last_four',
                'account_holder_type',
                'account_last_four',
                'routing_number',
            ]);
        });
    }
};
