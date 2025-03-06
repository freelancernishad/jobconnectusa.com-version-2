<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionColumnsToUserPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('user_packages', 'business_name')) {
                $table->string('business_name')
                      ->nullable()
                      ->after('ends_at');
            }

            if (!Schema::hasColumn('user_packages', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')
                      ->nullable()
                      ->after('business_name')
                      ->comment('Stripe subscription ID for recurring payments');
            }

            if (!Schema::hasColumn('user_packages', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')
                      ->nullable()
                      ->after('stripe_subscription_id')
                      ->comment('Stripe customer ID for recurring payments');
            }

            if (!Schema::hasColumn('user_packages', 'status')) {
                $table->string('status')
                      ->default('active')
                      ->after('stripe_customer_id')
                      ->comment('Subscription status: active, canceled, expired');
            }

            if (!Schema::hasColumn('user_packages', 'canceled_at')) {
                $table->timestamp('canceled_at')
                      ->nullable()
                      ->after('status')
                      ->comment('Timestamp when the subscription was canceled');
            }

            if (!Schema::hasColumn('user_packages', 'next_billing_at')) {
                $table->timestamp('next_billing_at')
                      ->nullable()
                      ->after('canceled_at')
                      ->comment('Next billing date for recurring subscriptions');
            }
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_packages', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn([
                'stripe_subscription_id',
                'stripe_customer_id',
                'status',
                'canceled_at',
                'next_billing_at',
            ]);
        });
    }
}
