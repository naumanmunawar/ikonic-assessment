<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Response;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $to = $request->to;
        $from = $request->from;
        $order = Order::whereBetween('created_at', [$from, $to])->get();

        $data = [
            'count' => $order->count(),
            'commissions_owed' => $order->sum('commission_owed'),
            'revenue' => $order->sum('subtotal'),
        ];

        return Response::json($data);
    }
}
