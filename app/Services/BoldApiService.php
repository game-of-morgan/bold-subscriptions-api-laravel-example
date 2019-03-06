<?php
namespace App\Services;

use App\Handlers\BoldApiRequestHandler;
use App\Handlers\BoldApiResponseHandler;
use function GuzzleHttp\choose_handler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class BoldApiService {

    const BOLD_API_BASE_URL = 'https://ro.boldapps.net/api/';

    protected $client;
    protected $requestHandler;
    protected $responseHandler;

    public function __construct()
    {
        $this->requestHandler = new BoldApiRequestHandler(env('BOLD_API_PRIVATE_KEY'), env('BOLD_API_HANDLE'), env('MYSHOPIFY_DOMAIN'));
        $this->responseHandler = new BoldApiResponseHandler($this->requestHandler);

        $stack = HandlerStack::create(choose_handler());
        $stack->push(Middleware::mapRequest($this->requestHandler));
        $stack->push(Middleware::mapResponse($this->responseHandler));

        /**
         * Setup basic headers for making API requests
         *
         * See BoldApiRequestHandler for further detail on what happens
         * before each request goes out (e.g. adding the authorization
         * header)
         */
        $this->client = new Client([
            'handler' => $stack,
            'base_uri' => static::BOLD_API_BASE_URL.'third_party/',
            'headers' => [
                'cache-control' => 'no-cache',
                'content-type' => 'application/json',
            ],
        ]);
    }

    /**
     * Update next order date for a customer's subscription
     *
     * @param $shopifyCustomerId
     * @param $subscriptionId
     * @param $nextOrderDate
     * @return bool
     */
    public function updateNextOrderDate($shopifyCustomerId, $subscriptionId, $nextOrderDate)
    {
        /**
         * Make the request to the API
         * Note: Adding the auth token header is handled in BoldApiRequestHandler
         */
        $res = $this->client->put('manage/subscription/orders/'.$subscriptionId.'/next_ship_date?customer_id='.$shopifyCustomerId, [
            'json' => [
                'next_shipping_date' => $nextOrderDate,
            ]
        ]);

        $result = json_decode($res->getBody(), true);

        /**
         * Need to ensure status is 200, otherwise there is an error
         */
        return $res->getStatusCode() === 200 && json_last_error() === JSON_ERROR_NONE ? $result : false;
    }
}
