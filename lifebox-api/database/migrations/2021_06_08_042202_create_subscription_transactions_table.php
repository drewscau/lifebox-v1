<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('subscription_id');
            $table->string('type')->nullable();                             // both Android and IOS (type)
            $table->string('transaction_id')->nullable();                   // both Android and IOS (id)
            $table->string('original_transaction_id')->nullable();          // only for IOS
            $table->string('purchase_token')->nullable();                   // only for Android
            $table->longText('signature')->nullable();                        // only for Android
            $table->longText('receipt')->nullable();                        // both Android (receipt) and IOS (appStoreReceipt)
            $table->longText('developerPayload')->nullable();               // only for Android
            $table->timestamps();

            $table->unique(['subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_transactions');
    }
}
