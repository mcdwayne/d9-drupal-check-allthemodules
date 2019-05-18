<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/23/17
 * Time: 1:50 PM
 */

namespace Drupal\log_monitor;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\RfcLogLevel;

class LogMonitorHelper {


  /**
   * Creates a list of database log administration filters that can be applied.
   *
   * @return array
   */
  public static function getFilters() {
    $filters = [];

    $db = Database::getConnection();
    $message_types = $db->query('SELECT DISTINCT(type) from {log_monitor_log} ORDER BY type')
      ->fetchAllKeyed(0, 0);

    foreach($message_types as $type) {
      $types[$type] = t($type);
    }

    if(!empty($types)) {
      $filters['type'] = [
        'title' => t('Type'),
        'where' => "l.type = ?",
        'options' => $types,
      ];
    }

    $filters['severity'] = [
      'title' => t('Severity'),
      'where' => 'l.severity = ?',
      'options' => RfcLogLevel::getLevels(),
    ];

    return $filters;

  }

  /**
   * Formats a database log message.
   *
   * @param object $row
   *   The record from the watchdog table. The object properties are: wid, uid,
   *   severity, type, timestamp, message, variables, link, name.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   The formatted log message or FALSE if the message or variables properties
   *   are not set.
   */
  public static function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message, $row->variables)) {
      $variables = @unserialize($row->variables);
      // Messages without variables or user specified text.
      if ($variables === NULL) {
        $message = Xss::filterAdmin($row->message);
      }
      elseif (!is_array($variables)) {
        $message = t('Log data is corrupted and cannot be unserialized: @message', ['@message' => Xss::filterAdmin($row->message)]);
      }
      // Message to translate with injected variables.
      else {
        $message = t(Xss::filterAdmin($row->message), $variables);
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }

  /**
   * Returns the next date/time when logs for this entity should be processed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The rule entity
   * @param \DateTime $last_run
   *     A DateTime object specifying the last time it was run
   *
   * @return \DateTime
   *    A DateTime object specifying the next time it should be processed
   */
  public static function getNextRun(EntityInterface $entity, \DateTime $last_run) {
    $scheduler = $entity->getScheduler();
    $interval = $scheduler->getInterval();
    $next_run = $last_run->add(new \DateInterval($interval));
    return $next_run;
  }

  /**
   * Check if the hold time for the entity's logs is expired.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public static function isExpired(EntityInterface $entity) {
    $processed = \Drupal::state()->get('log_monitor.' . $entity->id() . '.hold');
    if (is_null($processed)) {
      return FALSE;
    }
    $hold_time = new \DateInterval('P7D'); //@TODO: Give this option to user, assume 7 days for now
    $now = new \DateTime('now');
    if ($now >= $processed->add($hold_time)) {
      \Drupal::state()->delete('log_monitor.' . $entity->id() . '.hold');
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
