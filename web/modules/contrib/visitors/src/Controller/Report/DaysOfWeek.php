<?php

/**
 * @file
 * Contains Drupal\visitors\Controller\Report\DaysOfWeek.
 */

namespace Drupal\visitors\Controller\Report;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DaysOfWeek extends ControllerBase {
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
   * Constructs a DaysOfWeek object.
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
   * Returns a days of week page page.
   *
   * @return array
   *   A render array representing the days of week page content.
   */
  public function display() {
    $config    = \Drupal::config('visitors.config');
    $form      = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
    $header    = $this->_getHeader();
    $results   = $this->_getData(NULL);
    $sort_days = array_keys($this->_getDaysOfWeek());
    $results   = $this->_getData(NULL);
    $days      = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    $x         = array();
    $y         = array();

    foreach($days as $day) {
      $x[] = '"' . $day . '"';
      $y[$day] = 0;
    }

    foreach ($results as $data) {
      $y[$data[1]->__tostring()] = $data[2];
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
        'data' => t('#'),
      ),
      'day' => array(
        'data' => t('Day'),
      ),
      'count' => array(
        'data' => t('Pages'),
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
      visitors_date_format_sql('visitors_date_time', '%a'), 'd'
    );
    $query->addExpression(
      visitors_date_format_sql('MIN(visitors_date_time)', '%w'), 'n'
    );
    visitors_date_filter_sql_condition($query);
    $query->groupBy('d');
    $query->orderBy('n');
    $results = $query->execute();

    $rows = array();
    $i = 0;
    $tmp_rows = array();

    foreach ($results as $data) {
      $tmp_rows[$data->n] = array(
        $data->d,
        $data->count,
        $data->n
      );
    }
    $sort_days = $this->_getDaysOfWeek();

    foreach ($sort_days as $day => $value) {
      $rows[$value] = array($value, t($day), 0);
    }

    foreach ($tmp_rows as $tmp_item) {
      $day_of_week = Unicode::ucfirst(mb_strtolower($tmp_item[0]));
      $rows[$sort_days[$day_of_week]][2] = $tmp_item[1];
    }

    return $rows;
  }

  /**
   * Create days of week array, using first_day parameter,
   * using keys as day of week.
   *
   * @return array
   */
  protected function _getDaysOfWeek() {
    $days           = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    $date_first_day = \Drupal::config('system.date')->get('first_day', 0);
    $sort_days      = array();
    $n              = 1;

    for ($i = $date_first_day; $i < 7; $i++) {
      $sort_days[$days[$i]] = $n++;
    }

    for ($i = 0; $i < $date_first_day; $i++) {
      $sort_days[$days[$i]] = $n++;
    }

    return $sort_days;
  }
}

