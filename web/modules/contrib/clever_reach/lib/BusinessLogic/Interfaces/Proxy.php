<?php

namespace CleverReach\BusinessLogic\Interfaces;

use CleverReach\Infrastructure\Utility\HttpResponse;

/**
 * Interface Proxy
 *
 * @package CleverReach\BusinessLogic\Interfaces
 */
interface Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Call HTTP client.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE etc.)
     * @param string $endpoint Specific endpoint that should be called.
     * @param array|null $body Request body.
     * @param string $accessToken User access token.
     *
     * @return HttpResponse
     *   Response object that contains status, headers and body.
     */
    public function call($method, $endpoint, $body = array(), $accessToken = '');
}
