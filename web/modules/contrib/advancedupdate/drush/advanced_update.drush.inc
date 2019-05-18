<?php

/**
 * @file
 * Contains advanced_update.drush.inc.
 */

use Drupal\advanced_update\AdvancedUpdateStatus;

/**
 * Drush command hook.
 *
 * @return array
 *    List of commands.
 */
function advanced_update_drush_command() {
  $items['adup'] = array(
    'description' => "Apply advanced updates available",
    'arguments' => array(
      'module_name' => 'A name of a specific module to perform update',
      'direction' => 'The update direction (up or down)',
      'number' => 'Max number of updates to perform',
    ),
    'options' => array(
      'report' => 'Display a report of selected updates',
    ),
    'examples' => array(
      'drush adup' => 'Perform all availables advanced updates.',
      'drush adup mymodule' => 'Perform all advanced updates up for the mymodule module',
      'drush adup mymodule down' => 'Perform all advanced updates down for the mymodule module',
      'drush adup mymodule up 2' => 'Perform the next 2 advanced updates up for the mymodule module',
      'drush adup --report' => 'Display all advanced updates up available.',
      'drush adup mymodule --report' => 'display a list of available updates up for the mymodule module',
      'drush adup mymodule down 1 --report' => 'display the next update down available for the mymodule module',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );
  return $items;
}

/**
 * Perform all update tasks.
 *
 * @param string $module_name
 *    The name of module to update.
 * @param string $update_type
 *    The update direction (up or down).
 * @param string $number
 *    Max number of updates to perform for a specified module.
 */
function drush_advanced_update_adup($module_name = NULL, $update_type = NULL, $number = NULL) {
  $ask_report = drush_get_option('report');
  $update_manager = \Drupal::service('advanced_update.advanced_update_manager');
  if ($ask_report === TRUE) {
    $update_status = $update_manager->getReport($update_type, $module_name, $number);
    drush_advanced_update_display_status($update_status, $ask_report);
  }
  else {
    $update_status = $update_manager->execute($update_type, $module_name, $number);
    drush_advanced_update_display_status($update_status, $ask_report);

    // Cache rebuild.
    if (!empty($update_status)) {
      drush_invoke_process('@self', 'cr', array(), array());
    }
  }
}

/**
 * Display the array of AdvancedUpdateStatus returned by AdvancedUpdateManager.
 *
 * @param array $update_status
 *    An array of AdvancedUpdateStatus.
 * @param bool $ask_report
 *    Tell if it is a report request or not.
 */
function drush_advanced_update_display_status($update_status, $ask_report) {
  drush_print_r('');
  if (!empty($update_status)) {
    if ($ask_report) {
      drush_print_r((string) \Drupal::translation()
        ->translate('Available advanced updates:'));
    }

    $module_name = NULL;
    foreach ($update_status as $status) {
      $entity = $status->getUpdateEntity();

      if ($entity->moduleName() !== $module_name) {
        $module_name = $entity->moduleName();
        drush_print_r('');
        drush_print_r($module_name);
      }
      $message = date('Y-m-d H:i:s', $entity->date()) . ' - ' . $entity->label();
      if ($ask_report) {
        drush_print_r($message);
      }
      else {
        drush_log($message, $status->getStatus() === AdvancedUpdateStatus::STATUS_DONE ? 'success' : 'error');
        if ($status->getStatus() === AdvancedUpdateStatus::STATUS_FAILED) {
          $error_message = (string) \Drupal::translation()
            ->translate('error message: @message', array(
              '@message' => $status->getMessage(),
            ));
          drush_print_r($error_message);
        }
      }
    }
  }
  else {
    drush_print_r((string) \Drupal::translation()
      ->translate('No advanced updates available'));
  }
}
