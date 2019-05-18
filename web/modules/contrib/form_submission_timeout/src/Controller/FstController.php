<?php
/**
 * @file
 * Contains \Drupal\form_submission_timeout\Controller\FstController.
 */

namespace Drupal\form_submission_timeout\Controller;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns common functionality for form_submission_timeout.
 * @package Drupal\form_submission_timeout\Controller
 */
class FstController extends ControllerBase {

  /**
   * Function to remove a form entry from settings form.
   */
  public function removeFormid($name = NULL, $form_id = NULL, $action = NULL) {
    $this->updateFormIds($name, $form_id, 'remove');
    $response = $this->redirect('form_submission_timeout.' . $action);
    return $response;
  }

  /**
   * Update new values into the variable.
   *
   * @param $name
   * Name of the variable to update (array name)
   *
   * @param $value
   * Variable values to be updated (array to merge)
   *
   * @param $process
   * Add or remove items from the variable (array)
   */
  public static function updateFormIds($name, $value, $process = 'add') {
    $form_ids = \Drupal::config('form_submission_timeout.settings')->get($name);
    switch ($process) {
      case 'add':
        $form_ids = array_merge($form_ids, $value);
        break;
      case 'update':
        $form_ids = $value;
        break;
      case 'remove':
        unset($form_ids[$value]);
        break;
    }

    \Drupal::configFactory()->getEditable('form_submission_timeout.settings')
      ->set($name, $form_ids)
      ->save();
  }

  /**
   * Function to convert start and end date to timestamps.
   *
   * @param $form_id
   * Current form id (string)
   *
   * @return array
   */
  public static function convertDateToTimestamps($form_id) {
    $date_ar = array();
    $timed_form_ids = \Drupal::config('form_submission_timeout.settings')->get('sub_out_stop_form_ids');

    if ($timed_form_ids && array_key_exists($form_id, $timed_form_ids)) {
      $frequency = $timed_form_ids[$form_id]['sub_out_timeout_frequency'];
      $start_timed = $timed_form_ids[$form_id]['sub_out_start_date'];
      $end_timed = $timed_form_ids[$form_id]['sub_out_stop_date'];

      // Convert start and end date into date format Y-m-d H:i:s.
      if ($frequency == 'once') {
        $startdate = $start_timed . ' ' . $timed_form_ids[$form_id]['sub_out_start_timeout_period'] . ':00';
        $enddate = $start_timed . ' ' . $timed_form_ids[$form_id]['sub_out_stop_timeout_period'] . ':00';

        $date_ar['start'] = strtotime($startdate);
        $date_ar['end'] = strtotime($enddate);
      }
      else {
        $startdate = $start_timed . ' ' . $timed_form_ids[$form_id]['sub_out_start_timeout_period'] . ':00';
        $enddate = $end_timed . ' ' . $timed_form_ids[$form_id]['sub_out_stop_timeout_period'] . ':00';

        $date_ar['start'] = strtotime($startdate);
        $date_ar['end'] = strtotime($enddate);
      }

      $date_ar['frequency'] = $frequency;
      return $date_ar;
    }
  }

  /**
   *
   * Convert to seconds per day :
   * 60 sec/min * 60 min/hr * 24 hr/day = 86400 sec/day.
   *
   * @param $remaining_time
   * Calculated remaining time
   *
   * @return array
   */
  public static function calRemainingTime($remaining_time) {
    $time = array();
    $temp = $remaining_time / 86400;
    $time['days'] = floor($temp);
    $temp = 24 * ($temp - $time['days']);
    $time['hours'] = floor($temp);
    $temp = 60 * ($temp - $time['hours']);
    $time['minutes'] = floor($temp);
    $temp = 60 * ($temp - $time['minutes']);
    $time['seconds'] = floor($temp);
    return $time;
  }
}
