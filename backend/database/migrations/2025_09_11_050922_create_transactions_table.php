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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('journey_id')->nullable()->constrained('journeys')->onDelete('set null');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('type'); // 'TripFare', 'CashTopUp', 'StripeTopUp', 'Refund'
            $table->decimal('amount', 8, 2);
            $table->string('payment_method'); // 'Wallet', 'Cash', 'Stripe'
            $table->json('fare_details')->nullable(); 
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_charge_id')->nullable()->index();
            $table->string('status')->default('succeeded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
