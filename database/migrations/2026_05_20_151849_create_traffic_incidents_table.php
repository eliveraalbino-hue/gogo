<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('traffic_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_type'); // Clean groups: Vehicular Accident, Stalled Vehicle, etc.
            $table->text('description');     // Original raw description text from CSV
            $table->text('location');        // CHANGED TO text TO PREVENT TRUNCATION ERRORS
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->dateTime('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('traffic_incidents');
    }
};