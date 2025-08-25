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
        Schema::create('cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->date('date');
            $table->decimal('system_cash', 8, 2);
            $table->decimal('received_cash', 8, 2);
            $table->decimal('difference', 8, 2);
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['driver_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_reconciliations');
    }
};
