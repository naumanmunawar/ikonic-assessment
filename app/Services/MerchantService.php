<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT
        ];

        $user = User::create($user_data);

        $user_record = User::find($user->id);

        $merchant_data = [
            'user_id' => $user_record->id,
            'domain' => $data['domain'],
            'display_name' => $data['name']
        ];

        return Merchant::create($merchant_data);
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $merchant = Merchant::where('user_id', $user->id)->first();
        $merchant_data = [
            'domain' => $data['domain'],
            'display_name' => $data['name']
        ];


        return $merchant->update($merchant_data);
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user =  User::where('email', $email)->first();
        return isset($user->merchant) ? $user->merchant : null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        // TODO: Complete this method
        $orders = $affiliate->orders;

        foreach ($orders as $order) {
            if ($order->refresh()->payout_status == Order::STATUS_PAID) {
                Queue::push(function (PayoutOrderJob $job) use ($order) {
                    return $job->order->is($order);
                });
            }
        }
    }
}
