<?php

namespace Drupal\openid_connect_rest\Plugin;

use Exception;

use Drupal\Core\Url;
use Drupal\Core\Language\LanguageInterface;

/**
* Trait OpenIDConnectRESTClientTrait.
*
* @package Drupal\openid_connect_rest\Plugin
*/
trait OpenIDConnectRESTClientTrait {

  /**
   * Overrides OpenIDConnectClientBase::retrieveTokens().
   *
   * @param string $authorization_code
   *   A authorization code string.
   *
   * @return array|bool
   *   A result array or false.
   */
  public function retrieveTokens($authorization_code) {
    // Exchange `code` for access token and ID token.
    $language_none = \Drupal::languageManager()
      ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);
    $redirect_uri = Url::fromRoute(
      'openid_connect_rest.api.authenticate',
      [
        'client_name' => $this->pluginId,
      ],
      [
        'absolute' => TRUE,
        'language' => $language_none,
      ]
    )->toString();
    $endpoints = $this->getEndpoints();

    $request_options = [
      'form_params' => [
        'code' => $authorization_code,
        'client_id' => $this->configuration['client_id'],
        'client_secret' => $this->configuration['client_secret'],
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
      ],
    ];

    /* @var \GuzzleHttp\ClientInterface $client */
    $client = $this->httpClient;
    try {
      $response = $client->post($endpoints['token'], $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      // Expected result.
      $tokens = [
        'id_token' => isset($response_data['id_token']) ? $response_data['id_token'] : NULL,
        'access_token' => isset($response_data['access_token']) ? $response_data['access_token'] : NULL,
      ];
      if (array_key_exists('expires_in', $response_data)) {
        $tokens['expire'] = REQUEST_TIME + $response_data['expires_in'];
      }
      if (array_key_exists('refresh_token', $response_data)) {
        $tokens['refresh_token'] = $response_data['refresh_token'];
      }
      return $tokens;
    }
    catch (Exception $e) {
      $variables = [
        '@message' => 'Could not retrieve tokens',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('openid_connect_rest' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

}
