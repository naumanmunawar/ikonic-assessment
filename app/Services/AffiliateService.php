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
use App\Services\ApiService;
use Illuminate\Foundation\Testing\WithFaker;


class AffiliateService
{
    use WithFaker;

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

        if($merchant->user->email == $email) { //check if email use as merchant

            //$user = $this->createNewUser($name, $email); //create new user 
            $user_id = $merchant->user->id;
            $merchant_id = $merchant->id;

        } else {

            $user = $this->createNewUser($name, $email); //create new user 
            $user_id = $user->id;
            $merchant_id = $merchant->id;
        }


        $apiService = $this->apiService->createDiscountCode($merchant);


        $affiliate_check = Affiliate::where('user_id', $user_id)
        ->where('merchant_id', $merchant_id)
        ->where('commissionRate', $commissionRate)
        ->where('discount_code', $apiService['code'])
        ->first();

        if(!$affiliate_check) {
            $affiliate_data = [
                'user_id' => $user_id,
                'merchant_id' => $merchant_id,
                'commission_rate' => $commissionRate,
                'discount_code' => $apiService['code']
            ];
        }

        $affiliate = Affiliate::create($affiliate_data);

        if($affiliate) {
            Mail::to($email)->send(new AffiliateCreated($affiliate));
        }

        return $affiliate;

    }

    public function createNewUser($name, $email) {

        $user_check = User::where('email', $email)->first();

        if(!$user_check) {
            $user_data = [
                'name' => $name,
                'email' => $email,
                'password' => Str::uuid(),
                'type' => User::TYPE_AFFILIATE
            ];

            $user = User::create($user_data);
            return User::find($user->id);
        }
        
        return $user_check;
    }
}
