<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the payment status: global finance aggregations filter completed
     * payments constantly. The transporter_id foreign key already carries its
     * own index, and trips are covered by (status, departure_date).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
