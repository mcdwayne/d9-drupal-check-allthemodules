<?php

namespace Drupal\dblog_persistent\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\dblog\Controller\DbLogController;
use Drupal\dblog_persistent\DbLogPersistentStorageInterface;
use Drupal\dblog_persistent\Entity\ChannelInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DbLogPersistentController extends ControllerBase {

  /**
   * @var \Drupal\dblog_persistent\DbLogPersistentStorageInterface
   */
  protected $storage;

  /**
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * DbLogChannelViewBuilder constructor.
   *
   * @param \Drupal\dblog_persistent\DbLogPersistentStorageInterface $storage
   * @param \Drupal\user\UserStorageInterface $userStorage
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   */
  public function __construct(DbLogPersistentStorageInterface $storage,
                              UserStorageInterface $userStorage,
                              DateFormatterInterface $dateFormatter) {
    $this->storage = $storage;
    $this->userStorage = $userStorage;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('dblog_persistent.storage'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays a listing of database log messages.
   *
   * @param \Drupal\dblog_persistent\Entity\ChannelInterface $dblog_persistent_channel
   *
   * @return array*
   */
  public function overview(ChannelInterface $dblog_persistent_channel): array {
    $channel = $dblog_persistent_channel;
    $rows = [];
    $classes = DbLogController::getLogLevelClassMap();

    $header = [
      // Icon column.
      '',
      [
        'data'  => $this->t('Type'),
        'field' => 'w.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data'  => $this->t('Date'),
        'field' => 'w.wid',
        'sort'  => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
      [
        'data'  => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data'  => $this->t('Operations'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    foreach ($this->storage->getChannel($channel->id(),
      50,
      $header) as $dblog) {
      $message = $this->formatMessage($dblog);
      if ($message && isset($dblog->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)),
          256,
          TRUE,
          TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        $message = [
          '#type' => 'link',
          '#title' => $log_text,
          '#url' => Url::fromRoute('dblog_persistent.event',
            ['event_id' => $dblog->wid],
            [
              'attributes' => [
                // Provide a title for the link for useful hover hints. The
                // Attribute object will escape any unsafe HTML entities in the
                // final text.
                'title' => $title,
              ],
            ]
          )
        ];
      }
      $username = [
        '#theme'   => 'username',
        '#account' => $this->userStorage->load($dblog->uid),
      ];
      $rows[] = [
        'data'  => [
          // Cells.
          ['class' => ['icon']],
          $this->t($dblog->type),
          $this->dateFormatter->format($dblog->timestamp, 'short'),
          ['data' => $message],
          ['data' => $username],
          ['data' => ['#markup' => $dblog->link]],
        ],
        // Attributes for table row.
        'class' => [
          Html::getClass('dblog-' . $dblog->type),
          $classes[$dblog->severity],
        ],
      ];
    }

    $build['dblog_table'] = [
      '#type'       => 'table',
      '#header'     => $header,
      '#rows'       => $rows,
      '#attributes' => ['id' => 'admin-dblog', 'class' => ['admin-dblog']],
      '#empty'      => $this->t('No log messages available.'),
      '#attached'   => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];
    $build['dblog_pager'] = ['#type' => 'pager'];

    return $build;

  }

  public function overviewTitle(ChannelInterface $dblog_persistent_channel) {
    return $this->t('All events in %channel', [
      '%channel' => $dblog_persistent_channel->label()
    ]);
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
  public function eventDetails($event_id): array {
    $build = [];
    if ($dblog = $this->storage->getEvent($event_id)) {
      $severity = RfcLogLevel::getLevels();
      $message = $this->formatMessage($dblog);
      $username = [
        '#theme'   => 'username',
        '#account' => $dblog->uid ? $this->userStorage->load($dblog->uid) :
          User::getAnonymousUser(),
      ];
      $rows = [
        [
          ['data' => $this->t('Type'), 'header' => TRUE],
          $this->t($dblog->type),
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
          Link::fromTextAndUrl(
            $dblog->location,
            $dblog->location ?
              Url::fromUri($dblog->location) :
              Url::fromRoute('<none>')
          ),
        ],
        [
          ['data' => $this->t('Referrer'), 'header' => TRUE],
          Link::fromTextAndUrl(
            $dblog->referer,
            $dblog->referer ?
              Url::fromUri($dblog->referer) :
              Url::fromRoute('<none>')
          ),
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
        '#type'       => 'table',
        '#rows'       => $rows,
        '#attributes' => ['class' => ['dblog-event']],
        '#attached'   => [
          'library' => ['dblog/drupal.dblog'],
        ],
      ];
    }

    return $build;
  }

  public function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message, $row->variables)) {
      $variables = @unserialize($row->variables);
      // Messages without variables or user specified text.
      if ($variables === NULL) {
        $message = Xss::filterAdmin($row->message);
      }
      elseif (!\is_array($variables)) {
        $message = $this->t('Log data is corrupted and cannot be unserialized: @message',
          ['@message' => Xss::filterAdmin($row->message)]);
      }
      // Message to translate with injected variables.
      else {
        $message = $this->t(Xss::filterAdmin($row->message), $variables);
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }
}
