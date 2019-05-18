<?php

namespace Drupal\Tests\jsonrpc\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\RequestOptions;

/**
 * Class JsonRpcTestBase.
 *
 * @package Drupal\jsonrpc\Tests\Functional
 */
abstract class JsonRpcTestBase extends BrowserTestBase {

  /**
   * Post a request in JSON format.
   *
   * @param array $rpc_request
   *   The request structure that will be sent as JSON.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to be used for Basic Auth authentication.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Exceptions from the Guzzle client.
   */
  protected function postRpc(array $rpc_request, AccountInterface $account = NULL) {
    $url = $this->buildUrl(Url::fromRoute('jsonrpc.handler'));
    $request_options = [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::ALLOW_REDIRECTS => FALSE,
      RequestOptions::JSON => $rpc_request,
    ];

    if (NULL !== $account) {
      $request_options[RequestOptions::AUTH] = [
        $account->getAccountName(),
        $account->passRaw,
      ];
    }

    $client = $this->getHttpClient();
    return $client->request('POST', $url, $request_options);
  }

  /**
   * JSON RPC request using the GET method.
   *
   * @param array $rpc_request
   *   The request structure that will be sent as JSON.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to be used for Basic Auth authentication.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getRpc(array $rpc_request, AccountInterface $account = NULL) {
    $url = $this->buildUrl(Url::fromRoute('jsonrpc.handler'));
    $request_options = [
      RequestOptions::HTTP_ERRORS => FALSE,
      RequestOptions::ALLOW_REDIRECTS => FALSE,
      RequestOptions::QUERY => ['query' => Json::encode($rpc_request)],
    ];

    if ($account !== NULL) {
      $request_options[RequestOptions::AUTH] = [
        $account->getAccountName(),
        $account->passRaw,
      ];
    }

    $client = $this->getHttpClient();
    return $client->request('GET', $url, $request_options);
  }

}
