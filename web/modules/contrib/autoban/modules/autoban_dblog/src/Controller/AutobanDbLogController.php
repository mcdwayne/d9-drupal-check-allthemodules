<?php

/**
 * @file
 * Contains \Drupal\autoban_dblog\Controller\AutobanDbLogController.php .
 */

namespace Drupal\autoban_dblog\Controller;

use Drupal\dblog\Controller\DbLogController;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\autoban\Controller\AutobanController;

class AutobanDbLogController extends DbLogController {

  protected function buildFilterQuery() {
    return parent::buildFilterQuery();
  }

  /**
   * Override overview() method.
   */
  public function overview() {

    $autobanController = new AutobanController();
    $filter = $this->buildFilterQuery();
    $rows = [];

    $classes = static::getLogLevelClassMap();

    $this->moduleHandler->loadInclude('dblog', 'admin.inc');

    $build['dblog_filter_form'] = $this->formBuilder->getForm('Drupal\dblog\Form\DblogFilterForm');

    $header = [
      // Icon column.
      '',
      [
        'data' => $this->t('Type'),
        'field' => 'w.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'w.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
      [
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('IP address'),
        'field' => 'w.hostname',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Operations'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

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
      'hostname',
    ]);
    $query->leftJoin('users_field_data', 'ufd', 'w.uid = ufd.uid');

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $dblog) {

      $message = $this->formatMessage($dblog);

      if ($message && isset($dblog->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        // The link generator will escape any unsafe HTML entities in the final
        // text.
        $message = $this->l($log_text, new Url('dblog.event', ['event_id' => $dblog->wid], [
          'attributes' => [
            // Provide a title for the link for useful hover hints. The
            // Attribute object will escape any unsafe HTML entities in the
            // final text.
            'title' => $title,
          ],
        ]));
      }

      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($dblog->uid),
      ];

      $ip = $dblog->hostname;

      if (!empty($ip) && $autobanController->canIpBan($ip)) {
        // Retrieve BanProviders list.
        $providers = [];
        $banManagerList = $autobanController->getBanProvidersList();
        if (!empty($banManagerList)) {
          $destination = $this->getDestinationArray();
          foreach ($banManagerList as $id => $item) {
            $providers[$id] = $this->l($item['name'],
              new Url('autoban.direct_ban', ['ips' => $ip, 'provider' => $id], [
                'query' => [
                  'destination' => $destination['destination'],
                ],
              ]));
          }
        }
      }

      $providers_list = !empty($providers) ? ' ' . implode(', ', $providers) : '';
      $rows[] = [
        'data' => [
          // Cells.
          ['class' => ['icon']],
          $this->t($dblog->type),
          $this->dateFormatter->format($dblog->timestamp, 'short'),
          $message,
          ['data' => $username],
          $ip,
          ['data' => ['#markup' => $dblog->link . $providers_list]],
        ],
        // Attributes for table row.
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
