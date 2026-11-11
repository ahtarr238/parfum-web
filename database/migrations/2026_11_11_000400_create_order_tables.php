<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20);
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_purchase')->default(0);
            $table->unsignedInteger('quota');
            $table->unsignedInteger('used')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending_payment')->index();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_cost');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');
            $table->string('courier', 20)->nullable();
            $table->string('courier_service', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->json('address_snapshot');
            $table->foreignId('voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedSmallInteger('size_ml');
            $table->unsignedInteger('price');
            $table->unsignedInteger('qty');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('midtrans_order_id', 50)->unique();
            $table->string('snap_token')->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->string('status', 30)->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vouchers');
    }
};
