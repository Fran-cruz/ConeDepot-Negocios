<?php

use App\Models\CustomerRewardCoupon;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:customer')->get('/customer/account/rewards', function () {
    return view('customer-rewards', [
        'rewards' => CustomerRewardCoupon::with('coupon')
            ->where('customer_id', auth('customer')->id())
            ->whereHas('coupon', function ($query) {
                $query->where(function ($query) {
                    $query->where('usage_limit', 0)
                        ->orWhereColumn('times_used', '<', 'usage_limit');
                });
            })
            ->latest()
            ->get(),
    ]);
})->name('shop.customers.account.rewards');
