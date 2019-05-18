<?php

namespace Drupal\revue;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class RevueApi.
 *
 * @package Drupal\revue
 */
class RevueApi implements RevueApiInterface {

  use StringTranslationTrait;

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * RevueApi constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   The HTTP client service.
   */
  public function __construct(Client $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe($api_key, $email, $first_name = '', $last_name = '') {
    $request_body = [
      'email' => $email,
    ];
    if (!empty($first_name)) {
      $request_body['first_name'] = $first_name;
    }
    if (!empty($last_name)) {
      $request_body['last_name'] = $last_name;
    }

    try {
      $this->client->post(self::REVUE_API_URL . self::REVUE_API_SUBSCRIBERS_PATH, [
        'headers' => [
          'Authorization' => 'Token token="' . $api_key . '"',
        ],
        'form_params' => $request_body,
      ]);
    }
    catch (RequestException $e) {
      $message = $e->getMessage();

      $response = $e->getResponse();
      if ($response->getStatusCode() == 400) {
        $response_data = json_decode($response->getBody());
        $response_error = $response_data->error;
        if (isset($response_error->email)) {
          if (!empty($response_error->email)) {
            if (strpos($response_error->email[0], 'not confirmed yet') !== FALSE) {
              $message = $this->t('This email address has already been subscribed, but was not confirmed yet.');
            }
          }
        }
      }

      throw new RevueApiException($message);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileUrl($api_key) {
    try {
      $response = $this->client->get(self::REVUE_API_URL . self::REVUE_API_ACCOUNT_PATH, [
        'headers' => [
          'Authorization' => 'Token token="' . $api_key . '"',
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return isset($data['profile_url']) ? $data['profile_url'] : '';
    }
    catch (RequestException $e) {
      $message = $e->getMessage();
      throw new RevueApiException($message);
    }
  }

}
