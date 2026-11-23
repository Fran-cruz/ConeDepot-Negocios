<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\CartRule\Models\CartRuleCouponProxy;

class CustomerRewardCoupon extends Model
{
    protected $table = 'customer_reward_coupons';

    protected $guarded = [];

    public function coupon()
    {
        return $this->belongsTo(CartRuleCouponProxy::modelClass(), 'cart_rule_coupon_id');
    }
}
