<?php

namespace Drupal\urban_airship_web_push_notifications;

use GuzzleHttp\Exception\RequestException;

/**
 * Urban Airship Schedules API Integration.
 */
class SchedulesApi {

  protected $name;
  protected $local_scheduled_time;
  protected $push;

  /**
   * Set Name
   * @see https://docs.urbanairship.com/api/ua/#schedules-api
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * Get Name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set Scheduled Time
   * @see https://docs.urbanairship.com/api/ua/#schedules-api
   */
  public function setLocalScheduledTime($local_scheduled_time) {
    $this->local_scheduled_time = $local_scheduled_time;
    return $this;
  }

  /**
   * Get Scheduled Time
   */
  public function getLocalScheduledTime() {
    return ['local_scheduled_time' => $this->local_scheduled_time];
  }

  /**
   * Set Push API object
   * @see https://docs.urbanairship.com/api/ua/#push-object
   * @see https://docs.urbanairship.com/api/ua/#schedules-api
   */
  public function setPush($push) {
    $this->push = $push;
    return $this;
  }

  /**
   * Get Push API object
   */
  public function getPush() {
    return $this->push;
  }

  /**
   * Build Push Object
   * @see https://docs.urbanairship.com/api/ua/#push-object
   */
  public function getData() {
    $data = [
      'name'     => $this->getName(),
      'schedule' => $this->getLocalScheduledTime(),
      'push'     => $this->getPush(),
    ];
    return $data;
  }

  /**
   * Send scheduled notification to Urban Airship
   * @see https://docs.urbanairship.com/api/ua/#schedules-api
   */
  public function schedule() {
    try {
      $response = \Drupal::httpClient()->post('https://go.urbanairship.com/api/schedules', [
        'headers' => $this->headers(),
        'body'    => json_encode($this->getData()),
      ]);
      \Drupal::logger('urban_airship_web_push_notifications')->info('Notification successfully scheduled');
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
