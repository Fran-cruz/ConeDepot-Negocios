<?php

namespace App\Services;

use App\Models\CustomerRewardCoupon;
use Illuminate\Support\Facades\DB;
use Webkul\CartRule\Models\CartRule;
use Webkul\CartRule\Models\CartRuleCoupon;

class CustomerRewardService
{
    public function issueIfEligible($order): void
    {
        if (! $order->customer_id || in_array($order->status, ['canceled', 'closed'])) return;

        $threshold = 2000;
        $spent = (float) DB::table('orders')
            ->where('customer_id', $order->customer_id)
            ->whereNotIn('status', ['canceled', 'closed'])
            ->sum('grand_total');

        if ($spent < $threshold || CustomerRewardCoupon::where('customer_id', $order->customer_id)->where('threshold', $threshold)->exists()) return;

        $rule = CartRule::firstOrCreate(
            ['name' => 'Recompensa por compras acumuladas - 10%'],
            [
                'description' => 'Cupón automático al acumular L 2,000 en compras.', 'status' => 1,
                'coupon_type' => 2, 'usage_per_customer' => 1, 'uses_per_coupon' => 1,
                'action_type' => 'by_percent', 'discount_amount' => 10, 'sort_order' => 999,
            ]
        );

        $rule->cart_rule_channels()->syncWithoutDetaching([core()->getCurrentChannel()->id ?? 1]);
        $rule->cart_rule_customer_groups()->syncWithoutDetaching([1]);

        $code = 'CONE10-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $coupon = CartRuleCoupon::create([
            'cart_rule_id' => $rule->id, 'code' => $code, 'usage_limit' => 1,
            'usage_per_customer' => 1, 'is_primary' => 0,
        ]);

        CustomerRewardCoupon::create([
            'customer_id' => $order->customer_id, 'cart_rule_coupon_id' => $coupon->id,
            'threshold' => $threshold, 'discount_percent' => 10,
        ]);
    }
}
