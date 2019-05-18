<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/25/17
 * Time: 2:29 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Formatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Database\Database;
use Drupal\log_monitor\Logger\LogMonitorLog;
use Drupal\log_monitor\LogMonitorHelper;

/**
 * @LogMonitorFormatter(
 *   id = "detail",
 *   title = @Translation("Detail"),
 *   description = @Translation("Get all log messages with details in the email itself."),
 * )
 */
class Detail extends FormatterPluginBase {


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

    $rows = [];
    foreach ($logs as $log) {
      $message = LogMonitorHelper::formatMessage($log);
      $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
      $rows[$log->wid]['severity'] = $severity[$log->severity];
      $rows[$log->wid]['type'] = $log->type;
      $rows[$log->wid]['date'] = \Drupal::service('date.formatter')->format($log->timestamp, 'short');
      $rows[$log->wid]['message'] = Unicode::truncate($title, 56, TRUE, TRUE);
    }

    $message = '<table><tr><th>Severity</th><th>Type</th><th>Date</th><th>Message</th></tr>';
    foreach ($rows as $row) {
      $message .= '<tr><td>' . $row['severity'] . '</td><td>' . $row['type'] . '</td><td>' . $row['date'] . '</td><td>' . $row['message'] . '</td></tr>';
    }
    $message .= '</table>';

    return $message;
  }

}
