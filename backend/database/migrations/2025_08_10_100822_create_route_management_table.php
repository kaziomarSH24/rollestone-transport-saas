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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->string('route_prefix')->nullable();
            $table->text('google_map_link')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
            $table->string('location_name');
            $table->unsignedInteger('stop_order');
            $table->unsignedInteger('minutes_from_start')->default(0);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();
        });

        Schema::create('fares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
            $table->string('passenger_type'); // e.g., 'Child', 'Adult'
            $table->string('payment_method'); // e.g., 'Cash', 'Wallet'
            $table->decimal('amount', 8, 2);
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
             $table->string('trip_number')->nullable();
            $table->time('departure_time');
             $table->enum('direction', ['outbound', 'inbound']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

             $table->unique(['company_id', 'trip_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //for foreign key constraints
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('routes');
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('fares');
        Schema::dropIfExists('trips');
    }
};
