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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('departure_destination_id')->constrained('destinations')->restrictOnDelete();
            $table->foreignId('arrival_destination_id')->constrained('destinations')->restrictOnDelete();
            $table->date('departure_date');
            $table->time('departure_time');
            $table->unsignedInteger('price_per_seat');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('available_seats');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['status', 'departure_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
