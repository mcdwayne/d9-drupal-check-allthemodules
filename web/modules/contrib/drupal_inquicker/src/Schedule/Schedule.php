<?php

namespace Drupal\drupal_inquicker\Schedule;

/**
 * Represents a scheduled time.
 */
class Schedule {

  /**
   * Constructor.
   *
   * @param string $time
   *   A time such as 2019-03-13T09:20:00-06:00.
   * @param array $type
   *   A registration type such as ['id' => "emergency", 'name' => "ER"].
   * @param string $base_registration_url
   *   A registration URL such as https://example.inquicker.com/1.
   *   See the "Send to registration page" section of
   *   https://docs.inquicker.com/api/v2/#resources-list-schedules
   *   on how this will be used to construct a registration url.
   * @param array $extras
   *   Any extra information to add to this data, as key-value pairs. Extra
   *   information will **override** existing information, for example if you
   *   put ['location' => 'something'] here, then because location is not a
   *   key in the base data, it will be added to the base data; however if you
   *   'type_id' or 'url', or any other key as defined in ::data(), the
   *   data in extras will override and delete the base data.
   */
  public function __construct(string $time, array $type, string $base_registration_url, array $extras = []) {
    $this->time = $time;
    $this->type = $type;
    $this->base_registration_url = $base_registration_url;
    $this->extras = $extras;
  }

  /**
   * Get raw data.
   *
   * @return array
   *   Raw data.
   */
  public function data() : array {
    return array_merge([
      'time' => $this->time,
      'type' => $this->type,
      'type_id' => $this->type['id'],
      'type_name' => $this->type['name'],
      'url' => $this->base_registration_url . '?at=' . $this->time . '&appointment_type=' . $this->type['id'],
    ], $this->extras);
  }

  /**
   * Get the type IDs and names.
   *
   * @return array
   *   The type ids and names, for example:
   *   [
   *     'my-type' => ['name' => 'My Type'],
   *   ].
   */
  public function types() : array {
    return [
      $this->type['id'] => $this->type,
    ];
  }

}
