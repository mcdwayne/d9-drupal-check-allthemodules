<?php

namespace Drupal\drupal_inquicker\Schedule;

use Drupal\drupal_inquicker\Utilities\Collection;

/**
 * A list of times for a hospital and a service line.
 */
class ScheduleCollection extends Collection {

  /**
   * Constructor.
   *
   * @param array $data
   *   Information about this collection of times, from Inquicker.
   * @param string $default_type_name
   *   Certain Inquicker appointments do not have a type; however we still
   *   need to categorize them and format them as if they did. For such cases,
   *   set the desired human-readable name for output.
   */
  public function __construct(array $data, string $default_type_name = 'default') {
    parent::__construct();
    $this->data = $data;

    foreach ($data as $group) {
      foreach ($group['availableTimes'] as $date) {
        foreach ($date['times'] as $time) {

          $this->addDefaultAppointType($time, $default_type_name);

          foreach ($time['appointmentTypes'] as $type) {
            $this->add([
              new Schedule($time['time'], $type, $group['registrationUrl'], $this->invokeHook('drupal_inquicker_alter_schedule', [$time, $group])),
            ]);
          }
        }
      }
    }
  }

  /**
   * Add a default appointment type in case the structure has none.
   *
   * @param array $data
   *   The data structure which will be modified if it has no appointment type.
   * @param string $default_type_name
   *   The human-readable default type name.
   */
  public function addDefaultAppointType(array &$data, string $default_type_name) {
    if (!count($data['appointmentTypes'])) {
      $data['appointmentTypes'][] = [
        'id' => 'default_appointment_type',
        'name' => $default_type_name,
        'remote_id' => NULL,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function itemClass() : string {
    return Schedule::class;
  }

}
