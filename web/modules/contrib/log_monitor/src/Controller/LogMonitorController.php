<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/16/17
 * Time: 1:35 PM
 */

namespace Drupal\log_monitor\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\log_monitor\LogMonitorHelper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Log Monitor routes
 *
 * Class LogMonitorController
 *
 * @package Drupal\log_monitor\Controller
 */
class LogMonitorController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a LogMonitorController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->userStorage = $this->entityManager()->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Displays a listing of database log messages.
   *
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @see Drupal\dblog\Controller\DbLogController::eventDetails()
   */
  public function overview() {

    $filter = $this->buildFilterQuery();
    $rows = [];

    $build['log_monitor_filter_form'] = $this->formBuilder->getForm('Drupal\log_monitor\Form\LogMonitorFilterForm');

    $header = [
      [
        'data' => $this->t('Type'),
        'field' => 'l.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM]],
      [
        'data' => $this->t('Date'),
        'field' => 'l.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW]],
      [
        'data' => $this->t('Status'),
        'field' => 'l.status',
        'class' => [RESPONSIVE_PRIORITY_LOW]],
      $this->t('Message'),
      [
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM]],
      [
        'data' => $this->t('Dependencies'),
        'field' => 'ld.dependencies',
        'class' => [RESPONSIVE_PRIORITY_LOW]],
    ];

    $query = $this->database->select('log_monitor_log', 'l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('l', [
      'wid',
      'uid',
      'type',
      'message',
      'variables',
      'severity',
      'link',
      'timestamp',
      'status',
    ]);
    $query->fields('ld', [
      'dependencies',
    ]);
    $query->leftJoin('users_field_data', 'ufd', 'l.uid = ufd.uid');
    $query->leftJoin($this->database->queryTemporary('SELECT wid, COUNT(*) as dependencies FROM {log_monitor_log_dependencies} GROUP BY wid'), 'ld', 'l.wid = ld.wid');

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $log) {
      $message = LogMonitorHelper::formatMessage($log);
      if ($message && isset($log->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        $message = Link::fromTextAndUrl($log_text, new Url('log_monitor.event', ['event_id' => $log->wid], [
          'attributes' => [
            // Provide a title for the link for useful hover hints. The
            // Attribute object will escape any unsafe HTML entities in the
            // final text.
            'title' => $title,
          ],
        ]));
      }

      $status = [
        '0' => 'Needs validation',
        '1' => 'Claimed',
        '2' => 'Matched',
        '3' => 'Processed',
      ];
      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($log->uid),
      ];
      $rows[] = [
        'data' => [
          // Cells.
          $this->t($log->type),
          $this->dateFormatter->format($log->timestamp, 'short'),
          $this->t($status[$log->status]),
          $message,
          ['data' => $username],
          is_null($log->dependencies) ? 0 : $log->dependencies,
        ],
      ];
    }

    $build['log_monitor_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No log messages available'),
    ];
    $build['log_monitor_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Builds a query for database log administration filters based on session.
   *
   * @return array|null
   *   An associative array with keys 'where' and 'args' or NULL if there were
   *   no filters set.
   */
  protected function buildFilterQuery() {
    if (empty($_SESSION['log_monitor_overview_filter'])) {
      return;
    }

    $filters = LogMonitorHelper::getFilters();

    // Build query.
    $where = $args = [];
    foreach ($_SESSION['log_monitor_overview_filter'] as $key => $filter) {
      $filter_where = [];
      foreach ($filter as $value) {
        $filter_where[] = $filters[$key]['where'];
        $args[] = $value;
      }
      if (!empty($filter_where)) {
        $where[] = '(' . implode(' OR ', $filter_where) . ')';
      }
    }
    $where = !empty($where) ? implode(' AND ', $where) : '';

    return [
      'where' => $where,
      'args' => $args,
    ];
  }

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
    if ($log = $this->database->query('SELECT l.*, u.uid FROM {log_monitor_log} l LEFT JOIN {users} u ON u.uid = l.uid WHERE l.wid = :id', [':id' => $event_id])->fetchObject()) {
      $message = LogMonitorHelper::formatMessage($log);
      $username = [
        '#theme' => 'username',
        '#account' => $log->uid ? $this->userStorage->load($log->uid) : User::getAnonymousUser(),
      ];
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
      $rows = [
        [
          ['data' => $this->t('Type'), 'header' => TRUE],
          $this->t($log->type),
        ],
        [
          ['data' => $this->t('Date'), 'header' => TRUE],
          $this->dateFormatter->format($log->timestamp, 'long'),
        ],
        [
          ['data' => $this->t('User'), 'header' => TRUE],
          ['data' => $username],
        ],
        [
          ['data' => $this->t('Location'), 'header' => TRUE],
          $this->l($log->location, $log->location ? Url::fromUri($log->location) : Url::fromRoute('<none>')),
        ],
        [
          ['data' => $this->t('Referrer'), 'header' => TRUE],
          $this->l($log->referer, $log->referer ? Url::fromUri($log->referer) : Url::fromRoute('<none>')),
        ],
        [
          ['data' => $this->t('Message'), 'header' => TRUE],
          $message,
        ],
        [
          ['data' => $this->t('Severity'), 'header' => TRUE],
          $severity[$log->severity],
        ],
        [
          ['data' => $this->t('Hostname'), 'header' => TRUE],
          $log->hostname,
        ],
        [
          ['data' => $this->t('Operations'), 'header' => TRUE],
          ['data' => ['#markup' => $log->link]],
        ],
      ];
      $build['log_table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => ['class' => ['log-event']],
      ];
    }

    return $build;
  }

  /**
   * Displays logs related to a specific entity.
   *
   * @param string $entity_id
   *   Unique ID of the entity for which to show logs.
   *
   * @return array
   *   A build array in the format expected by drupal_render();
   */
  public function entityOverview($entity_id) {

    $filter = $this->buildFilterQuery();
    $rows = [];

    $build['log_monitor_filter_form'] = $this->formBuilder->getForm('Drupal\log_monitor\Form\LogMonitorFilterForm');

    $header = [
      [
        'data' => $this->t('Type'),
        'field' => 'l.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM]],
      [
        'data' => $this->t('Date'),
        'field' => 'l.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW]],
      $this->t('Message'),
      [
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM]],
      $this->t('Expiry'),
    ];

    $query = $this->database->select('log_monitor_log', 'l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('l', [
      'wid',
      'uid',
      'type',
      'message',
      'variables',
      'severity',
      'link',
      'timestamp',
      'status',
    ]);
    $query->fields('ld', [
      'entity_id',
    ]);
    $query->condition('entity_id', $entity_id);
    $query->leftJoin('users_field_data', 'ufd', 'l.uid = ufd.uid');
    $query->leftJoin('log_monitor_log_dependencies', 'ld', 'l.wid = ld.wid');

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    // @TODO: Change according to LogMonitorHelper::isExpired()
    $expiry = \Drupal::state()->get('log_monitor.' . $entity_id . '.hold')->add(new \DateInterval('P7D'));

    foreach ($result as $log) {
      $message = LogMonitorHelper::formatMessage($log);
      if ($message && isset($log->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        $message = Link::fromTextAndUrl($log_text, new Url('log_monitor.event', ['event_id' => $log->wid], [
          'attributes' => [
            // Provide a title for the link for useful hover hints. The
            // Attribute object will escape any unsafe HTML entities in the
            // final text.
            'title' => $title,
          ],
        ]));
      }

      $status = [
        '0' => 'Needs validation',
        '1' => 'Claimed',
        '2' => 'Matched',
        '3' => 'Processed',
      ];
      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($log->uid),
      ];
      $rows[] = [
        'data' => [
          // Cells.
          $this->t($log->type),
          $this->dateFormatter->format($log->timestamp, 'short'),
          $message,
          ['data' => $username],
          $expiry->format('Y-m-d H:i:s'),
        ],
      ];
    }

    $build['log_monitor_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No log messages available'),
    ];
    $build['log_monitor_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
