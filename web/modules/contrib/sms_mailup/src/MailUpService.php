<?php

namespace Drupal\sms_mailup;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\Json;

/**
 * The MailUp service.
 */
class MailUpService implements MailUpServiceInterface {

  /**
   * The Drupal HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * MailUp authentication service.
   *
   * @var \Drupal\sms_mailup\MailupAuthenticationInterface
   */
  protected $mailupAuthentication;

  /**
   * Constructs a new MailUpService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\sms_mailup\MailupAuthenticationInterface $mailUpAuthentication
   *   MailUp authentication service.
   */
  function __construct(ClientInterface $http_client, MailupAuthenticationInterface $mailUpAuthentication) {
    $this->httpClient = $http_client;
    $this->mailupAuthentication = $mailUpAuthentication;
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails($gateway_id) {
    $provider = $this->mailupAuthentication
      ->createOAuthProvider($gateway_id);
    $token = $this->mailupAuthentication->getToken($gateway_id);
    if (FALSE === $token) {
      return FALSE;
    }

    $request = $provider->getAuthenticatedRequest(
      'GET',
      'https://services.mailup.com/API/v1.1/Rest/ConsoleService.svc/Console/Authentication/Info',
      $token
    );

    $response = $this->httpClient->send($request);
    $result = Json::decode((String) $response->getBody());
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  function getListSecret($username, $password, $guid) {
    $key = 'sms_mailup.list.secrets.' . $guid;
    if ($secret = \Drupal::state()->get($key)) {
      return $secret;
    }

    // @todo: GET, then POST if non-exist.

    $settings = [
      'auth' => [$username, $password],
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'ListGUID' => $guid,
      ],
    ];

    $url = 'https://sendsms.mailup.com/api/v2.0/lists/1/listsecret';
    try {
      $response = $this->httpClient
        ->request('post', $url, $settings);
    }
    catch (RequestException $e) {
      return NULL;
    }

    if ($response->getStatusCode() == 200) {
      // {"Data":{"ListSecret":"$the-secret"},"Code":"0","Description":"","State":"DONE"}.
      $body_encoded = (string) $response->getBody();
      $body = !empty($body_encoded) ? Json::decode($body_encoded) : [];
      if (!empty($body['Data']['ListSecret'])) {
        $secret = $body['Data']['ListSecret'];
        \Drupal::state()->set($key, $secret);
        return $secret;
      }
    }

    return NULL;
  }

}
