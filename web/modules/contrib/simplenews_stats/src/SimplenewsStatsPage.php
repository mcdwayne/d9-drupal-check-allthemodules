<?php

namespace Drupal\simplenews_stats;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use DatePeriod;
use DateTime;
use DateInterval;
use Drupal\Component\Utility\Html;

/**
 * Class SimplenewsStatsPage.
 */
class SimplenewsStatsPage {

  use StringTranslationTrait;

  /**
   * The entity SimpleNews.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The entity Newsletter from simplenews.
   * 
   * @var \Drupal\simplenews\Entity\Newsletter 
   */
  protected $simplenews;

  /**
   * The simplenews values.
   * 
   *  @var array 
   */
  protected $simplenewsValues;

  /**
   * All dates.
   * 
   *  @var array 
   */
  protected $dates;

  /**
   * Series.
   * 
   *  @var array 
   */
  protected $series;

  /**
   * Number of clicks.
   *
   * @var integer 
   */
  protected $countClick;

  /**
   * Number of views.
   *
   *  @var integer 
   */
  protected $countView;

  /**
   * The global simplenews stats.
   *
   * @var Drupal\simplenews_stats\Entity\SimplenewsStats
   */
  protected $simplenewsStats;

  /**
   * SimplenewsStatsPage Constructor.
   *
   * @param Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity used as simplenews.
   */
  public function __construct($entity) {
    if (!$entity instanceof EntityInterface) {

      $this->entity = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
        'title' => $this->t('Deleted'),
        'nid'   => 0,
        'type'  => 'deleted',
      ]);

      return;
    }

    $this->entity = $entity;
    if (!$this->entity->simplenews_issue->isEmpty()) {
      $this->simplenewsValues = $this->entity->simplenews_issue->first()->getValue();
      $this->simplenews       = $this->entity->simplenews_issue->entity;
    }
  }

  /**
   * Return the total of Clicks.
   *
   * @return int
   *   The number of clicks.
   */
  public function getCountClicks() {
    return $this->queryCount('click');
  }

  /**
   * Return the total of Views.
   *
   * @return int
   *   The number of views.
   */
  public function getCountViews() {
    return $this->queryCount('view');
  }

  /**
   * Return the total of Views.
   *
   * @return int
   *  The number of email sent.
   */
  public function getCountTotalMails() {
    $simplenews_stats = $this->getSimplenewsStats();
    return ($simplenews_stats) ? $simplenews_stats->getTotalMails() : 0;
  }

  /**
   * Return the detail of Clicks.
   *
   * @return array
   *   The detail of clicks formatted for chart.
   */
  public function getDetailClicks() {
    return [
      'label'           => 'Clicks',
      'backgroundColor' => '#4bc0c0',
      'borderColor'     => '#4bc0c0',
      'fill'            => FALSE,
      'data'            => $this->queryDetail('click'),
    ];
  }

  /**
   * Return the detail of view actions.
   *
   * @return array
   *   The detal of views formatted for chart.
   */
  public function getDetailViews() {
    return [
      'label'           => 'Views',
      'backgroundColor' => '#96f',
      'borderColor'     => '#96f',
      'fill'            => FALSE,
      'data'            => $this->queryDetail('view'),
    ];
  }

  /**
   * Calculation of percent.
   *
   * @param int $number
   *   The number to compare with total mail sent.
   *
   * @return int
   *   The percent.
   */
  public function getPercent($number) {
    if ($number) {
      $percent = number_format(($number / $this->getCountTotalMails()) * 100, 2);
    }
    else {
      $percent = 0;
    }
    return $this->t('@percent %', ['@percent' => $percent]);
  }

  /**
   * Return the most clicked links.
   *
   * @return array
   *   List of most clicked links.
   */
  public function getTopLinks() {
    $links = array();

    $query = Database::getConnection()
      ->select('simplenews_stats_item', 'ss');

    $query->fields('ss', ['route_path']);
    $query->addExpression('COUNT(ssiid)', 'number');
    $query->condition('entity_type', $this->entity->getEntityTypeId())
      ->condition('entity_id', $this->entity->id())
      ->condition('title', 'click')
      ->groupBy('route_path')
      ->orderBy('number', 'DESC');

    $results = $query->execute();

    foreach ($results as $data) {
      $links[] = ['route_path' => $data->route_path, 'count' => $data->number];
    }

    return $links;
  }

  /**
   * The statistics page.
   *
   * @return
   *   Array renderable of the page.
   */
  public function getpage() {

    $content           = [];
    $content['report'] = [
      '#theme'  => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Sent status'),
        $this->t('Views'),
        $this->t('Clicks'),
        $this->t('Total emails sent'),
        $this->t('% Views'),
        $this->t('% Clicks'),
      ],
      '#rows'   => [
        [
          $this->entity->toLink(),
          // @todo: Add status description.
          $this->simplenewsValues['status'],
          $this->getCountViews(),
          $this->getCountClicks(),
          $this->getCountTotalMails(),
          $this->getPercent($this->getCountViews()),
          $this->getPercent($this->getCountClicks()),
        ]
      ]
    ];

    $chart_lineid          = Html::getUniqueId('chart_line');
    $content['chart_line'] = [
      '#type'       => 'html_tag',
      '#tag'        => 'canvas',
      '#attributes' => [
        'id' => $chart_lineid,
      ],
      '#attached'   => [
        'library'        => ['simplenews_stats/simplenews_stats.chartjs'],
        'drupalSettings' => ['simplenews_stats' => [
            $chart_lineid => [
              'labels'   => $this->getDatesForCharts(),
              'datasets' => $this->getSeriesForCharts(),
            ],
          ],
        ],
      ],
    ];

    $content['paths'] = [
      '#prefix' => '<h2>' . $this->t('Top links') . '</h2>',
      '#theme'  => 'table',
      '#header' => [$this->t('Path'), $this->t('Count')],
      '#rows'   => $this->getTopLinks()
    ];

    return $content;
  }

  /**
   * Helper function for query detail.
   *
   * @param string $type
   *   The type of statistics (click,view)
   */
  protected function queryDetail($type) {
    $query = Database::getConnection()
      ->select('simplenews_stats_item', 'ss');

    $query->addExpression('COUNT(ssiid)', 'number');
    $query->addExpression("FROM_UNIXTIME(created,'%Y-%m-%d')", 'day');
    $query->condition('title', $type)
      ->condition('entity_type', $this->entity->getEntityTypeId())
      ->condition('entity_id', $this->entity->id())
      ->groupBy('day');

    $results = $query->execute();

    $data = [];
    foreach ($results as $result) {
      $data[$result->day] = (int) $result->number;
    }

    return $data;
  }

  /**
   * Helper function for count query.
   *
   * @param string $type 
   *   The type of statistics (click,view)
   *
   * @return int
   *   The count.
   */
  protected function queryCount($type) {
    $stored = &$this->{'count_' . $type};
    if ($stored != NULL) {
      return $stored;
    }

    $query = \Drupal::entityQuery('simplenews_stats_item')
      ->condition('entity_type', $this->entity->getEntityTypeId())
      ->condition('entity_id', $this->entity->id())
      ->condition('title', $type);

    // Affect new value before return it.
    $stored = $query->count()->execute();

    return $stored;
  }

  /**
   * Return an array of dates.
   *
   * @return array 
   *   Array of dates.
   */
  protected function getDates() {

    if (!empty($this->dates)){
      return $this->dates;
    }

    $dates = [];
    foreach ($this->getSeries() as $data) {
      $dates += $data['data'];
    }

    // Sort on keys(dates).
    ksort($dates);

    // Get first key(date).
    reset($dates);
    $start = key($dates);

    // Get last key(date).
    end($dates);
    $end = key($dates);

    $period = new DatePeriod(new DateTime($start), new DateInterval('P1D'), new DateTime($end . ' + 1 day'));

    $range = [];
    foreach ($period as $date) {
      $range[$date->format('Y-m-d')] = $date->format('Y-m-d');
    }

    $this->dates = $range;
    return $this->dates;
  }

  /**
   * Prepare dates for Charts module.
   *
   * @return array
   *   Array of dates.
   */
  protected function getDatesForCharts() {
    return array_values($this->getDates());
  }

  /**
   * Return all series datas.
   *
   * @return array
   *   The series.
   */
  protected function getSeries() {
    if (empty($this->series)) {
      $this->series[] = $this->getDetailClicks();
      $this->series[] = $this->getDetailViews();
    }
    return $this->series;
  }

  /**
   * Prepare series for Charts module.
   *
   * @return array
   *   The series for charts.
   */
  protected function getSeriesForCharts() {
    $series = $this->getSeries();
    foreach ($series as &$serie) {
      $data = [];
      foreach ($this->getDates() as $raw_date => $date) {
        $data[] = !empty($serie['data'][$raw_date]) ? $serie['data'][$raw_date] : 0;
      }
      $serie['data'] = $data;
    }

    return $series;
  }

  /**
   * Return the simplenews stats entity in relation to the entity.
   */
  protected function getSimplenewsStats() {
    return \Drupal::entityTypeManager()->getStorage('simplenews_stats')->getFromRelatedEntity($this->entity);
  }

}
