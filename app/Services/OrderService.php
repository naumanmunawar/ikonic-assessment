<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;
use Mail;
use App\Mail\AffiliateCreated;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
        $user = User::where('email', $data['customer_email'])->first(); 
        $affiliate = Affiliate::where('merchant_id', $merchant->id)->first();

        if(!$affiliate) {
            $affiliate = $this->createNewAffiliateRecord($data, $merchant);
        }

        $order = Order::where('subtotal', $data['order_id'])
        ->where('merchant_id', $merchant->id)
        ->where('affiliate_id', $affiliate->id)
        ->where('commission_owed', $data['subtotal_price'] * $affiliate->commission_rate)
        ->where('external_order_id', $data['order_id'])
        ->get();

        if(!$order) {
            $order_data = [
                'subtotal' => $data['subtotal_price'],
                'merchant_id' => $merchant->id,
                'affiliate_id' => $affiliate->id,
                'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
                'external_order_id' => $data['order_id']
            ];

            return Order::create($order_data);
        }
    }

    public function createNewAffiliateRecord($data, $merchant) {

        $user_data = [ //
            'name' => $data['customer_name'], //
            'email' => $data['customer_email'],
            'password' => Str::uuid(),
            'type' => User::TYPE_AFFILIATE
        ];

        $user = User::create($user_data);
        $user_record = User::find($user->id);

        $affiliate_data = [
            'user_id' => $user_record->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $merchant->default_commission_rate,
            'discount_code' => ''
        ];

        $affiliate = Affiliate::create($affiliate_data);
        Mail::to($data['customer_email'])->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
