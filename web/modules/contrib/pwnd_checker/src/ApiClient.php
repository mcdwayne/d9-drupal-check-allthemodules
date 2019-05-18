<?php

namespace Drupal\pwned_checker;

/**
 * Client for the HaveIBeenPwnd API.
 *
 * More info: https://haveibeenpwned.com/API/v2.
 */
class ApiClient {

  /**
   * Checks an e-mail address against the HaveIBeenPwnd API.
   *
   * @param string $email
   *   E-mail address to check.
   *
   * @return array
   *   Return an array of breaches if the e-mail address is known to the API,
   *   otherwise an empty array.
   */
  public function checkEmailAddress(string $email) {
    $config = \Drupal::config('pwnd_checker.settings');

    $url = $config->get('api_url') . '/breachedaccount/' . $email;

    try {
      $response = \Drupal::httpClient()->get(
        $url, [
          'headers' => [
            'Accept' => 'application/vnd.haveibeenpwned.v2+json',
            'User-Agent' => 'Pwnage-Checker-For-Drupal',
          ],
        ]
      );

      switch ($response->getStatusCode()) {
        case 200:
          return json_decode((string) $response->getBody());

        case 404:
          return [];

        default:
          \Drupal::logger('pwned_checker')->warning(
            'Unexpected response from the haveibeenpwned API : '
            . $response->getStatusCode() . ' - ' . $response->getReasonPhrase()
          );
          return [];
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('pwned_checker')->error(
        'Error accessing haveibeenpwned API : ' . $e->getMessage()
      );

      return [];
    }
  }

}
