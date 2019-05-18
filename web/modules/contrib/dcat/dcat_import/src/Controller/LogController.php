<?php

namespace Drupal\dcat_import\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dcat_import\Entity\DcatSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LogController.
 *
 * @package Drupal\dcat_import\Controller
 */
class LogController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * LogController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter service.
   */
  public function __construct(Connection $database, DateFormatterInterface $date_formatter) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * Show the log as a table.
   *
   * @param \Drupal\dcat_import\Entity\DcatSource $dcat_source
   *   The DCAT source to show to log for.
   *
   * @return array
   *   Build array.
   */
  public function logPage(DcatSource $dcat_source) {
    $header = [
      // Icon.
      '',
      [
        'data' => $this->t('Type'),
        'field' => 'l.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'l.id',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
    ];

    $query = $this->database->select('dcat_import_log', 'l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');

    $query->fields('l', ['id', 'type', 'import_start', 'message'])
      ->orderByHeader($header);

    $query->condition('source', $dcat_source->id());
    $result = $query->limit(50)->execute();

    $rows = [];
    foreach ($result as $log_row) {
      $rows[] = [
        'data' => [
          'icon' => ['class' => ['icon']],
          'type' => $log_row->type,
          'date' => $this->dateFormatter->format($log_row->import_start, 'short'),
          'message' => $log_row->message,
        ],
        'class' => [Html::getClass('dblog-' . $log_row->type)],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['class' => ['admin-dblog']],
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];
    $build['dblog_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
