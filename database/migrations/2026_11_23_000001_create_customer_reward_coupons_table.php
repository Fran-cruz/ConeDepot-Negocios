<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_reward_coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('cart_rule_coupon_id');
            $table->decimal('threshold', 12, 4);
            $table->decimal('discount_percent', 5, 2)->default(10);
            $table->timestamps();
            $table->unique(['customer_id', 'threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_reward_coupons');
    }
};
