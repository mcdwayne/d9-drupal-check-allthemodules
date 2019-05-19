<?php

/**
 * @file
 * Contains Drupal\visitors\Controller\Report\Referers.
 */

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Referer type constants.
 */
define('REFERER_TYPE_INTERNAL_PAGES', 1);
define('REFERER_TYPE_EXTERNAL_PAGES', 2);
define('REFERER_TYPE_ALL_PAGES',      3);

class Referers extends ControllerBase {
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
   * Constructs a Referers object.
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
   * Returns a referers page.
   *
   * @return array
   *   A render array representing the referers page content.
   */
  public function display() {
    $form = $this->formBuilder->getForm('Drupal\visitors\Form\Referers');
    $header = $this->_getHeader();

    return array(
      'visitors_date_filter_form' => $form,
      'visitors_table' => array(
        '#type'  => 'table',
        '#header' => $header,
        '#rows'   => $this->_getData($header),
      ),
      'visitors_pager' => array('#type' => 'pager')
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
      'visitors_referer' => array(
        'data'      => t('Referer'),
        'field'     => 'visitors_referer',
        'specifier' => 'visitors_referer',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'count' => array(
        'data'      => t('Count'),
        'field'     => 'count',
        'specifier' => 'count',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
        'sort'      => 'desc',
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
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->addExpression('COUNT(*)', 'count');
    $query->fields('v', array('visitors_referer'));
    visitors_date_filter_sql_condition($query);
    $this->_setReferersCondition($query);
    $query->groupBy('visitors_referer');
    $query->orderByHeader($header);
    $query->limit($items_per_page);

    $count_query = db_select('visitors', 'v');
    $count_query->addExpression('COUNT(DISTINCT visitors_referer)');
    visitors_date_filter_sql_condition($count_query);
    $this->_setReferersCondition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = array();

    $page = isset($_GET['page']) ? (int) $_GET['page'] : '';
    $i = 0 + $page * $items_per_page;
    foreach ($results as $data) {
      $rows[] = array(
        ++$i,
        empty($data->visitors_referer) ? $this->t('No Referer') : $data->visitors_referer,
        $data->count,
      );
    }
      return $rows;
    }

  /**
   * Build sql query from referer type value.
   */
  protected function _setReferersCondition(&$query) {
    switch ($_SESSION['referer_type']) {
      case REFERER_TYPE_INTERNAL_PAGES:
        $query->condition(
          'visitors_referer',
          sprintf('%%%s%%', $_SERVER['HTTP_HOST']),
          'LIKE'
        );
        $query->condition('visitors_referer', '', '<>');
        break;
      case REFERER_TYPE_EXTERNAL_PAGES:
        $query->condition(
          'visitors_referer',
          sprintf('%%%s%%', $_SERVER['HTTP_HOST']),
          'NOT LIKE'
        );
        break;
      default:
        break;
    }

    return $query;
  }
}

