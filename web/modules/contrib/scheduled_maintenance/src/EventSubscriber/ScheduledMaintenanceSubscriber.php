<?php
/**
 * @file
 * Contains \Drupal\scheduled_maintenance\EventSubscriber\InitSubscriber.
 */

namespace Drupal\scheduled_maintenance\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Utility\Html;

class ScheduledMaintenanceSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForScheduledMaintenance');
    return $events;
  }

  /**
   * Checks if a scheduled maintenance should be started.
   */
  public function checkForScheduledMaintenance() {
    // Get maintenance mode state.
    $maintenance_mode = \Drupal::state()->get('system.maintenance_mode');

    // Get scheduled maintenance time setting.
    $scheduled_time = \Drupal::config('scheduled_maintenance.settings')->get('time');

    // Validate that maintenance mode is not enabled and that a scheduled
    // maintenance time is set.
    if ($maintenance_mode || !$scheduled_time) {
      return;
    }

    // Validate the date and time format.
    if (!_scheduled_maintenance_validate_date($scheduled_time)) {
      return;
    }

    // Convert time to a timestamp.
    $timestamp = strtotime($scheduled_time);

    // If the scheduled time is less than or equal to the current time, enable
    // maintenance mode and clear the scheduled maintenance time setting.
    if ($timestamp <= REQUEST_TIME) {
      \Drupal::state()->set('system.maintenance_mode', 1);
      \Drupal::service('config.factory')->getEditable('scheduled_maintenance.settings')->clear('time')->save();
    }
    else {
      // Get message setting.
      $message = \Drupal::config('scheduled_maintenance.settings')->get('message');

      // Validate that there is a message.
      if (!$message) {
        return;
      }

      // Get time offset settings for message display.
      $offset_value = \Drupal::config('scheduled_maintenance.settings')->get('offset.value');
      $offset_unit = \Drupal::config('scheduled_maintenance.settings')->get('offset.unit');

      // Validate time offset unit to one of the allowed units.
      if (!$offset_unit || !array_key_exists($offset_unit, _scheduled_maintenance_get_allowed_units())) {
        return;
      }

      // Validate time offset value to a positive integer.
      if ($offset_value === '' || (!is_numeric($offset_value) || intval($offset_value) != $offset_value || $offset_value <= 0)) {
        return;
      }

      // Convert time offset to a timestamp.
      $message_timestamp = strtotime("-$offset_value $offset_unit", $timestamp);

      // If message time is less than or equal to the current time, show the
      // message.
      if ($message_timestamp <= REQUEST_TIME) {
        drupal_set_message($message, 'warning', FALSE);
      }
    }
  }

}
