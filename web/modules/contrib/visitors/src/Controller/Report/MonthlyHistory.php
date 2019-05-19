<?php

/**
 * @file
 * Contains Drupal\visitors\Controller\Report\MonthlyHistory.
 */

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MonthlyHistory extends ControllerBase {
  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $date;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a MonthlyHistory object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date
   *   The date service.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder) {
    $this->date        = $date_formatter;
    $this->formBuilder = $form_builder;
  }

  /**
   * Returns a monthly history page.
   *
   * @return array
   *   A render array representing the monthly history page content.
   */
  public function display() {
    $config  = \Drupal::config('visitors.config');
    $form    = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header  = $this->_getHeader();
    $results = $this->_getData(NULL);
    $x       = array();
    $y       = array();

    foreach ($results as $data) {
      $x[] = "\"$data[1]\"";
      $y[$data[1]] = $data[2];
    }

    return array(
      'visitors_date_filter_form' => $form,
      'visitors_jqplot' => array(
        '#theme'  => 'visitors_jqplot',
        '#path'   => drupal_get_path('module', 'visitors'),
        '#x'      => implode(', ', $x),
        '#y'      => implode(', ', $y),
        '#width'  => $config->get('chart_width'),
        '#height' => $config->get('chart_height'),
      ),
      'visitors_table' => array(
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->_getData($header),
      ),
    );
  }

  /**
   * Returns a table header configuration.
   *
   * @return array
   *   A render array representing the table header info.
   */
  protected function _getHeader() {
    return array(
      '#' => array(
        'data'      => t('#'),
      ),
      'm' => array(
        'data'      => t('Month'),
        'field'     => 'm',
        'specifier' => 'm',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
        'sort'      => 'asc',
      ),
      'count' => array(
        'data'      => t('Pages'),
        'field'     => 'count',
        'specifier' => 'count',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
  }

  /**
   * Returns a table content.
   *
   * @param array $header
   *   Table header configuration.
   *
   * @return array
   *   Array representing the table content.
   */
  protected function _getData($header) {
    $items_per_page = \Drupal::config('visitors.config')->get('items_per_page');

    $query = db_select('visitors', 'v')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression(
      visitors_date_format_sql('visitors_date_time', '%Y%m'), 'm'
    );
    $query->addExpression(
      visitors_date_format_sql('MIN(visitors_date_time)', '%Y %M'), 's'
    );
    visitors_date_filter_sql_condition($query);
    $query->groupBy('m');
    if (!is_null($header)) {
      $query
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($header);
    }
    $query->limit($items_per_page);

    $count_query = db_select('visitors', 'v');
    $count_query->addExpression(
      sprintf('COUNT(DISTINCT %s)',
      visitors_date_format_sql('visitors_date_time', '%Y %M'))
    );

    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = array();

    $page = isset($_GET['page']) ? (int) $_GET['page'] : '';
    $i = 0 + $page * $items_per_page;

    foreach ($results as $data) {
      $rows[] = array(
        ++$i,
        $data->s,
        $data->count
      );
    }

    return $rows;
  }
}

