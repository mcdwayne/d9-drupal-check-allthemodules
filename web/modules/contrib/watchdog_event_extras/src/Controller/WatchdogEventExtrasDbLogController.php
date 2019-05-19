<?php

namespace Drupal\watchdog_event_extras\Controller;

use Drupal\dblog\Controller\DbLogController;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Controller Class.
 */
class WatchdogEventExtrasDbLogController extends DbLogController {

  /**
   * Displays details about a specific database log message.
   *
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by drupal_render();
   */
  public function eventDetails($event_id) {
    $build = [];
    if ($dblog = $this->database->query('SELECT w.*, u.uid FROM {watchdog} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id', [':id' => $event_id])->fetchObject()) {
      $severity = RfcLogLevel::getLevels();
      $message = $this->formatMessage($dblog);
      $username = [
        '#theme' => 'username',
        '#account' => $dblog->uid ? $this->userStorage->load($dblog->uid) : User::getAnonymousUser(),
      ];
      $rows = [
        [
          ['data' => $this->t('Type'), 'header' => TRUE],
          $dblog->type,
        ],
        [
          ['data' => $this->t('Date'), 'header' => TRUE],
          $this->dateFormatter->format($dblog->timestamp, 'long'),
        ],
        [
          ['data' => $this->t('User'), 'header' => TRUE],
          ['data' => $username],
        ],
        [
          ['data' => $this->t('Location'), 'header' => TRUE],
          $this->l($dblog->location, $dblog->location ? Url::fromUri($dblog->location) : Url::fromRoute('<none>')),
        ],
        [
          ['data' => $this->t('Referrer'), 'header' => TRUE],
          $this->l($dblog->referer, $dblog->referer ? Url::fromUri($dblog->referer) : Url::fromRoute('<none>')),
        ],
        [
          ['data' => $this->t('Message'), 'header' => TRUE],
          $message,
        ],
        [
          ['data' => $this->t('Severity'), 'header' => TRUE],
          $severity[$dblog->severity],
        ],
        [
          ['data' => $this->t('Hostname'), 'header' => TRUE],
          $dblog->hostname,
        ],
        [
          ['data' => $this->t('Operations'), 'header' => TRUE],
          ['data' => ['#markup' => $dblog->link]],
        ],
      ];

      $build['dblog_table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => ['class' => ['dblog-event']],
        '#attached' => [
          'library' => ['dblog/drupal.dblog'],
        ],
      ];
    }
    // Get the plugin manager.
    $type = \Drupal::service('plugin.manager.wee');
    // Get the defined plugins.
    $plugin_definitions = $type->getDefinitions();
    // Loop, create instances and add to table.
    foreach ($plugin_definitions as $key => $value) {
      $p_instance = $type->createInstance($key);
      $build['dblog_table']['#rows'][] = [
        ['data' => $p_instance->title(), 'header' => TRUE],
        ['data' => ['#markup' => $p_instance->markup($dblog)]],
      ];
      // Call attached on instance.
      $p_instance->attached($build['dblog_table']['#attached'], $dblog);
    }
    return $build;
  }

}
