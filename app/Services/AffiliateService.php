<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method
        $check_user = User::where('email', $email)->first();

       // dd([$merchant->user->email, $email]);

        if(!$check_user) {
            $user_data = [
                'name' => $name,
                'email' => $email,
                'password' => Str::uuid(),
                'type' => User::TYPE_AFFILIATE
            ];

            $user = User::create($user_data);
            $user_record = User::find($user->id);
            $user_id = $user_record->id;
        } else {
            $user_id = $check_user->id;
        }

        $affiliate_data = [
            'user_id' => $user_id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => ''
        ];

        $affiliate = Affiliate::create($affiliate_data);
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;

    }
}
