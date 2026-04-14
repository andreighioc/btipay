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
        Schema::create('BtiPay_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_id')->nullable()->index()->comment('iPay UUID order ID');
            $table->string('order_number', 32)->index()->comment('Merchant order number');
            $table->string('payment_type', 10)->default('1phase')->comment('1phase or 2phase');
            $table->unsignedBigInteger('amount')->comment('Amount in minor currency units (bani)');
            $table->string('currency', 3)->default('946')->comment('ISO 4217 numeric currency code');
            $table->string('status', 30)->default('CREATED')->index()->comment('Transaction status');
            $table->string('action_code', 10)->nullable()->comment('Processing system action code');
            $table->string('action_code_description', 512)->nullable();
            $table->string('form_url', 512)->nullable()->comment('Payment form URL');
            $table->string('return_url', 512)->nullable();
            $table->text('description')->nullable();

            // Card info (masked)
            $table->string('masked_pan', 23)->nullable()->comment('Masked card number');
            $table->string('card_expiration', 6)->nullable()->comment('YYYYMM format');
            $table->string('cardholder_name', 64)->nullable();
            $table->string('approval_code', 6)->nullable();

            // References
            $table->string('auth_ref_num', 24)->nullable()->comment('RRN');
            $table->string('terminal_id', 10)->nullable();
            $table->string('payment_way', 32)->nullable()->comment('CARD / CARD_BINDING / BT_PAY');

            // Amounts tracking
            $table->unsignedBigInteger('approved_amount')->default(0);
            $table->unsignedBigInteger('deposited_amount')->default(0);
            $table->unsignedBigInteger('refunded_amount')->default(0);

            // ECI for 3DSecure
            $table->string('eci', 4)->nullable();

            // Customer info
            $table->string('customer_email', 254)->nullable();
            $table->string('customer_phone', 15)->nullable();
            $table->string('customer_ip', 45)->nullable();

            // Error tracking
            $table->string('error_code', 3)->nullable();
            $table->string('error_message', 512)->nullable();

            // Chargeback flag
            $table->boolean('chargeback')->default(false);

            // Loyalty
            $table->string('loyalty_order_id', 36)->nullable()->comment('UUID for LOY transaction');
            $table->unsignedBigInteger('loyalty_amount')->default(0)->comment('Amount paid in LOY');

            // Polymorphic relation to link to any model (Order, Booking, etc.)
            $table->nullableMorphs('payable');

            // Raw API responses for debugging
            $table->json('raw_register_response')->nullable();
            $table->json('raw_status_response')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('BtiPay_transactions');
    }
};
