<?php

namespace Drupal\drd\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\dblog\Controller\DbLogController;
use Drupal\drd\Entity\DomainInterface;

/**
 * Class Activity.
 *
 * @package Drupal\drd\Controller
 */
class Activity extends DbLogController {

  /**
   * Read the activity log and render that into a view.
   *
   * @param \Drupal\drd\Entity\DomainInterface $drd_domain
   *   The domain for which to display the activity.
   *
   * @return array
   *   Renderable array of the domain's activity.
   */
  public function view(DomainInterface $drd_domain) {
    $rows = [];
    $classes = static::getLogLevelClassMap();
    $this->moduleHandler->loadInclude('dblog', 'admin.inc');

    $header = [
      // Icon column.
      '',
      [
        'data' => $this->t('Date'),
        'field' => 'w.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
    ];

    /* @var \Drupal\Core\Database\Query\TableSortExtender $query */
    $query = $this->database->select('watchdog', 'w')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('w', [
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ])
      ->condition('w.link', $drd_domain->toUrl()->toString());

    $result = $query
      ->range(0, 50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $dblog) {
      $message = $this->formatMessage($dblog);
      if ($message && isset($dblog->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 512, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 156, TRUE, TRUE);
        $message = Link::fromTextAndUrl($log_text, new Url('dblog.event', ['event_id' => $dblog->wid], [
          'attributes' => [
            'title' => $title,
          ],
        ]));
      }
      $rows[] = [
        'data' => [
          ['class' => ['icon']],
          $this->dateFormatter->format($dblog->timestamp, 'short'),
          $message,
        ],
        'class' => [Html::getClass('dblog-' . $dblog->type), $classes[$dblog->severity]],
      ];
    }

    $build['dblog_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-dblog', 'class' => ['admin-dblog']],
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];
    $build['dblog_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
