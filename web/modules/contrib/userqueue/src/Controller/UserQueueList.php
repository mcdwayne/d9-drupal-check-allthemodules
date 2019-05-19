<?php
/**
 * @file
 * Contains \Drupal\userqueue\Controller\UserQueueList.
 */

namespace Drupal\userqueue\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the commerce module.
 */
class UserQueueList extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
      $container->get('database'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a userqueue object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->formBuilder = $form_builder;
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function AdminUserQueueList() {

    $header = array(
      array(
        'data' => t('Title'),
        'field' => 'uq.title',
        'sort' => 'asc'
      ),
      array(
        'data' => t('Queue length'),
        'field' => 'uq.size'
      ),
      array('data' => t('Operation'),'colspan' => '4'),
    );
    
    $query = $this->database->select('userqueue', 'uq')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
      
    $query->fields('uq',array('uqid', 'title', 'size', 'reverse'));

    $results = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    $queues = array();
    foreach ($results as $result) {
      $queues[$result->uqid] = $result;
    } 

    $rows = array();
    foreach ($queues as $queue) {
      $operations = array();
      $operations[] = \Drupal::l(t('View'), new Url('userqueue.admin_userqueue.uqid.view', array('uqid' => $queue->uqid)));
      $operations[] = \Drupal::l(t('Edit'), new Url('userqueue.admin_userqueue.uqid.edit', array('uqid' => $queue->uqid)));

      $rows[] = array(
        'data' => array(
          t($queue->title),
          $queue->size == 0 ? t('Infinite') : $queue->size,
	  \Drupal::l(t('Show'), new Url('userqueue.admin_userqueue.uqid.show', array('uqid' => $queue->uqid))),
          \Drupal::l(t('View'), new Url('userqueue.admin_userqueue.uqid.view', array('uqid' => $queue->uqid))),
          \Drupal::l(t('Edit'), new Url('userqueue.admin_userqueue.uqid.edit', array('uqid' => $queue->uqid))),
	  \Drupal::l(t('Delete'), new Url('userqueue.admin_userqueue.uqid.delete', array('uqid' => $queue->uqid))), 	
        )
      );
    }

    $build['admin_userqueue_list_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'admin-userqueue-list', 'class' => array('admin-userqueue')),
      '#empty' => $this->t('No user queues available.'),
    );

    $build['admin_userqueue_list_pager'] = array('#theme' => 'pager');

    return $build;
  }
}
