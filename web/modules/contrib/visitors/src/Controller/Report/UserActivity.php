<?php

/**
 * @file
 * Contains Drupal\visitors\Controller\Report\UserActivity.
 */

namespace Drupal\visitors\Controller\Report;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserActivity extends ControllerBase {
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
   * Constructs a UserActivity object.
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
   * Returns a user activity page.
   *
   * @return array
   *   A render array representing the user activity page content.
   */
  public function display() {
    $form = $this->formBuilder->getForm('Drupal\visitors\Form\DateFilter');
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
    $headers = array(
      '#' => array(
        'data'      => t('#'),
      ),
      'u.name' => array(
        'data'      => t('User'),
        'field'     => 'u.name',
        'specifier' => 'u.name',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'hits' => array(
        'data'      => t('Hits'),
        'field'     => 'hits',
        'specifier' => 'hits',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
        'sort'      => 'desc',
      ),
      'nodes' => array(
        'data'      => t('Nodes'),
        'field'     => 'nodes',
        'specifier' => 'nodes',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );

    if (module_exists('comment')) {
      $headers['comments'] = array(
        'data'      => t('Comments'),
        'field'     => 'comments',
        'specifier' => 'comments',
        'class'     => array(RESPONSIVE_PRIORITY_LOW),
      );
    }

    return $headers;
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
    $is_comment_module_exist = module_exists('comment');
    $items_per_page = \Drupal::config('visitors.config')->get('items_per_page');

    $query = db_select('users_field_data', 'u')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');

    $query->leftJoin('visitors', 'v', 'u.uid=v.visitors_uid');
    $query->leftJoin('node_field_data', 'nfd', 'nfd.uid=v.visitors_uid');
    $query->leftJoin('node', 'n', 'nfd.nid=n.nid');
    if ($is_comment_module_exist) {
      $query->leftJoin('comment', 'c', 'u.uid=c.uid');
    }
    $query->fields('u', array('name', 'uid'));
    $query->addExpression('COUNT(DISTINCT v.visitors_id)', 'hits');
    $query->addExpression('COUNT(DISTINCT n.nid)', 'nodes');
    if ($is_comment_module_exist) {
      $query->addExpression('COUNT(DISTINCT c.cid)', 'comments');
    }
    visitors_date_filter_sql_condition($query);
    $query->groupBy('u.name');
    $query->groupBy('u.uid');
    $query->groupBy('v.visitors_uid');
    $query->groupBy('nfd.uid');
    if ($is_comment_module_exist) {
      $query->groupBy('c.uid');
    }
    $query->orderByHeader($header);
    $query->limit($items_per_page);
  
    $count_query = db_select('users_field_data', 'u');
    $count_query->leftJoin('visitors', 'v', 'u.uid=v.visitors_uid');
    $count_query->addExpression('COUNT(DISTINCT u.uid)');
    visitors_date_filter_sql_condition($count_query);
    $query->setCountQuery($count_query);
    $results = $query->execute();

    $rows = array();

    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $i = 0 + $page * $items_per_page;

    foreach ($results as $data) {
      $user = user_load($data->uid);
      $username = array('#type' => 'username', '#account' => $user);
      if ($is_comment_module_exist) {
        $rows[] = array(
          ++$i,
          drupal_render($username),
          $data->hits,
          $data->nodes,
          $data->comments
        );
      }
      else {
        $rows[] = array(
          ++$i,
          drupal_render($username),
          $data->hits,
          $data->nodes
        );
      }
    }

    return $rows;
  }
}

