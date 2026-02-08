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
        Schema::create('seller_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('payment_method')->nullable();
            $table->string('trx_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'period_from', 'period_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_settlements');
    }
};

