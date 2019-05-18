<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/25/17
 * Time: 2:29 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Formatter;

/**
 * @LogMonitorFormatter(
 *   id = "summary",
 *   title = @Translation("Summary"),
 *   description = @Translation("Get a brief summary of log messages, with a link to view details."),
 * )
 */
class Summary extends FormatterPluginBase {


  /**
   * {@inheritdoc}
   */
  public function format($logs) {
    $severity = [
      '0' => 'Emergency',
      '1' => 'Alert',
      '2' => 'Critical',
      '3' => 'Error',
      '4' => 'Warning',
      '5' => 'Notice',
      '6' => 'Info',
      '7' => 'Debug',
    ];
    $count = [];
    foreach ($logs as $log) {
      $count['type'][$log->type] = 0;
      $count['severity'][$severity[$log->severity]] = 0;
    }
    foreach ($logs as $log) {
      $count['type'][$log->type]++;
      $count['severity'][$severity[$log->severity]]++;
    }
    $message = '<p>Your log summary for ' . \Drupal::config('system.site')->get('name') . ':</p>';
    foreach ($count['type'] as $type => $number) {
      $message .= '<p>' . $number . ' messages of type \'' . $type . '\'.</p>';
    }
    $message .= '<p>' . 'Out of those, ' . '</p>';
    foreach ($count['severity'] as $severity => $number) {
      $message .= '<p>' . $number . ' were ' . strtolower($severity) . 's.' . '</p>';
    }

    $detail_link = \Drupal::request()->getBaseUrl() . '/admin/reports/log_monitor/entity/' . reset($logs)->entity_id;
    $message .= '<p>To view details, please <a href="' . $detail_link . '">click here</a></p>';
    return $message;
  }

}
