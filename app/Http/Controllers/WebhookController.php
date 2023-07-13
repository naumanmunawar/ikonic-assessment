<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Response;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data_array = $request->all();
        $this->orderService->processOrder($data_array);

        return Response::json([]);

    }
}
