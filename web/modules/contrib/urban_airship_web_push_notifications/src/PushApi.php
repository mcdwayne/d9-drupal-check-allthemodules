<?php

namespace Drupal\urban_airship_web_push_notifications;

use GuzzleHttp\Exception\RequestException;

/**
 * Urban Airship Push API Integration.
 */
class PushApi {

  protected $audience;
  protected $device_types;
  protected $notification;

  /**
   * Set Audience
   * @see https://docs.urbanairship.com/api/ua/#audience-selectors
   */
  public function setAudience($audience) {
    $this->audience = $audience;
    return $this;
  }

  /**
   * Get Audience
   */
  public function getAudience() {
    return $this->audience;
  }

  /**
   * Set Device Types
   * @see https://docs.urbanairship.com/api/ua/#push-object
   */
  public function setDeviceTypes($types) {
    $this->device_types = $types;
    return $this;
  }

  /**
   * Get Device Types
   */
  public function getDeviceTypes() {
    return ($this->device_types != 'all') ? [$this->device_types] : $this->device_types;
  }

  /**
   * Set Notification message
   * @see https://docs.urbanairship.com/api/ua/#push-object
   * @see https://docs.urbanairship.com/api/ua/#web
   */
  public function setNotification($notification) {
    if (is_array($notification)) {
      if (!empty($notification['title'])) {
        $this->notification['web']['title'] = $notification['title'];
      }
      if (!empty($notification['body'])) {
        $this->notification['alert'] = $notification['body'];
        $this->notification['web']['alert'] = $notification['body'];
      }
      if (!empty($notification['icon'])) {
        $this->notification['web']['icon']['url'] = $notification['icon'];
      }
      if (!empty($notification['url'])) {
        $this->notification['actions']['open'] = [
          'type'    => 'url',
          'content' => $notification['url'],
        ];
      }
      if (!empty($notification['interaction'])) {
        $this->notification['web']['require_interaction'] = TRUE;
      }
    }
    else {
      $this->notification = [
        'alert' => $notification,
      ];
    }
    return $this;
  }

  /**
   * Get Notification message
   */
  public function getNotification() {
    return $this->notification;
  }

  /**
   * Build Push Object
   * @see https://docs.urbanairship.com/api/ua/#push-object
   */
  public function getData() {
    return [
      'audience'     => $this->getAudience(),
      'device_types' => $this->getDeviceTypes(),
      'notification' => $this->getNotification(),
    ];
  }

  /**
   * Send notification to Urban Airship
   * @see https://docs.urbanairship.com/api/ua/#push-api
   */
  public function send() {
    try {
      $response = \Drupal::httpClient()->post('https://go.urbanairship.com/api/push', [
        'headers' => $this->headers(),
        'body'    => json_encode($this->getData()),
      ]);
      \Drupal::logger('urban_airship_web_push_notifications')->info('Notification successfully sent');
    }
    catch (RequestException $e) {
      \Drupal::logger('urban_airship_web_push_notifications')->error($e->getMessage());
    }
  }

  /**
   * Authentication
   */
  protected function headers() {
    $config = \Drupal::config('urban_airship_web_push_notifications.configuration');
    return [
      'Accept'        => 'application/vnd.urbanairship+json; version=3',
      'Content-Type'  => 'application/json',
      'Authorization' => 'Basic ' . base64_encode($config->get('app_key') . ':' . $config->get('app_master_secret')),
    ];
  }

}
