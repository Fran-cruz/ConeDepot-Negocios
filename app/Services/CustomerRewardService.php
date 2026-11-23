<?php

namespace App\Services;

use App\Models\CustomerRewardCoupon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\CartRule\Models\CartRule;
use Webkul\CartRule\Models\CartRuleCoupon;

class CustomerRewardService
{
    /**
     * Amount, in store currency, the customer must accumulate per reward.
     */
    public const SPEND_THRESHOLD = 2000;

    /**
     * Discount percentage granted by each reward coupon.
     */
    public const DISCOUNT_PERCENT = 10;

    /**
     * Name of the single cart rule that holds every reward coupon.
     */
    public const RULE_NAME = 'Recompensa por compras acumuladas - 10%';

    /**
     * Issue one single-use coupon for every threshold the customer has reached.
     *
     * Never throws, so order placement is not affected.
     */
    public function issueIfEligible($order): void
    {
        if (! $order->customer_id || in_array($order->status, ['canceled', 'closed'], true)) {
            return;
        }

        try {
            $this->issue($order);
        } catch (Throwable $e) {
            Log::error('Customer reward coupon could not be issued', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function issue($order): void
    {
        $threshold = (float) self::SPEND_THRESHOLD;

        $spent = (float) DB::table('orders')
            ->where('customer_id', $order->customer_id)
            ->whereNotIn('status', ['canceled', 'closed'])
            ->sum('grand_total');

        /**
         * One reward per threshold reached: L 2,000 -> 1 coupon, L 4,000 -> 2, etc.
         */
        $earned = (int) floor($spent / $threshold);

        $issued = CustomerRewardCoupon::where('customer_id', $order->customer_id)
            ->where('threshold', $threshold)
            ->count();

        if ($earned <= $issued) {
            return;
        }

        $rule = $this->rewardRule();

        for ($i = $issued; $i < $earned; $i++) {
            $coupon = CartRuleCoupon::create([
                'cart_rule_id' => $rule->id,
                'code' => 'CONE10-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'usage_limit' => 1,
                'usage_per_customer' => 1,
                'is_primary' => 0,
            ]);

            CustomerRewardCoupon::create([
                'customer_id' => $order->customer_id,
                'cart_rule_coupon_id' => $coupon->id,
                'threshold' => $threshold,
                'discount_percent' => self::DISCOUNT_PERCENT,
            ]);
        }
    }

    /**
     * The shared cart rule behind every reward coupon.
     *
     * Rules must be linked to the customer group of the shopper and to the
     * storefront channel, otherwise Bagisto never evaluates them and the
     * coupon is accepted without any discount being applied.
     */
    protected function rewardRule(): CartRule
    {
        $rule = CartRule::updateOrCreate(
            ['name' => self::RULE_NAME],
            [
                'description' => 'Cupón automático al acumular L 2,000 en compras.',
                'status' => 1,
                'coupon_type' => 1,
                'use_auto_generation' => 0,
                'usage_per_customer' => 0,
                'uses_per_coupon' => 0,
                'condition_type' => 1,
                'conditions' => null,
                'end_other_rules' => 0,
                'action_type' => 'by_percent',
                'discount_amount' => self::DISCOUNT_PERCENT,
                'discount_quantity' => 0,
                'discount_step' => 1,
                'apply_to_shipping' => 0,
                'free_shipping' => 0,
                'sort_order' => 999,
            ]
        );

        $rule->cart_rule_channels()->syncWithoutDetaching(
            DB::table('channels')->pluck('id')->all() ?: [1]
        );

        $rule->cart_rule_customer_groups()->syncWithoutDetaching(
            DB::table('customer_groups')->pluck('id')->all() ?: [1]
        );

        return $rule;
    }
}
