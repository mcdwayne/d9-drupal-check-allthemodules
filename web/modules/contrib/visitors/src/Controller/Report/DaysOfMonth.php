<?php

/**
 * @file
 * Contains Drupal\visitors\Controller\Report\DaysOfMonth.
 */

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DaysOfMonth extends ControllerBase {
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
   * Constructs a DaysOfMonth object.
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
   * Returns a days of month page.
   *
   * @return array
   *   A render array representing the days of month page content.
   */
  public function display() {
    $config  = \Drupal::config('visitors.config');
    $form    = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header  = $this->_getHeader();
    $results = $this->_getData(NULL);

    $tmp_rows = array();
    $y = array();
    for ($i = 1; $i <= 31; $i++) {
      $y[$i] = 0;
    }

    foreach ($results as $data) {
      $y[$data[1]] = $data[2];
    }

    return array(
      'visitors_date_filter_form' => $form,
      'visitors_jqplot' => array(
        '#theme'  => 'visitors_jqplot',
        '#path'   => drupal_get_path('module', 'visitors'),
        '#x'      => implode(', ', range(1, 31)),
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
      'day' => array(
        'data'      => t('Day'),
        'field'     => 'day',
        'specifier' => 'day',
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
    $query = db_select('visitors', 'v');
    $query->addExpression('COUNT(*)', 'count');
    $query->addExpression(
      visitors_date_format_sql('visitors_date_time', '%d'), 'day'
    );
    $query->groupBy('day');
    visitors_date_filter_sql_condition($query);

    if (!is_null($header)) {
      $query
        ->extend('Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($header);
    }
    
    $results = $query->execute();
    $rows = array();
    $i = 0;
  
    foreach ($results as $data) {
      $rows[] = array(
        ++$i,
        (int) $data->day,
        $data->count
      );
    }

    return $rows;
  }
}

