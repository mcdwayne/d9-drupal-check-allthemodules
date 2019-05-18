<?php

namespace Drupal\chrome_push_notification\Model;

/**
 * Class ChromeApiCall.
 *
 * @package Drupal\chrome_push_notification\Model
 */
class ChromeApiCall {

  protected static $apiUrl = 'https://android.googleapis.com/gcm/send';
  protected $apiKey;
  public static $chromeNotificationTable = 'chrome_data';
  public static $chromeNotificationViewNumber = 50;

  /**
   * {@inheritdoc}
   */
  protected static function sendNotification(array $register_id) {
    $gpn_api_key = \Drupal::config('chrome_push_notification.gpn')->get('chrome_push_notification_api_key');
    if (!empty($register_id)) {
      $data = [
        'registration_ids' => $register_id,
      ];
      $fields_string = json_encode($data);
      $response = \Drupal::httpClient()
        ->post(self::$apiUrl, [
          'body' => $fields_string,
          'http_errors' => FALSE,
          'headers' => [
            'Authorization' => 'key=' . $gpn_api_key,
            'Content-Type' => 'application/json',
          ],
        ]);
      return $response;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function sendNotificationStart($registrationIds) {
    $incrementVariable = 30;
    for ($i = 0; $i < count($registrationIds); $i += $incrementVariable) {
      $registerId = [];
      for ($j = 0; $j < $incrementVariable; $j++) {
        if (!empty($registrationIds[$i + $j]->register_id)) {
          $registerId[] = $registrationIds[$i + $j]->register_id;
        }
      }
      if (!empty($registerId)) {
        self::sendNotification($registerId);
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function sendNotificationFinished() {
    return TRUE;
  }

}
