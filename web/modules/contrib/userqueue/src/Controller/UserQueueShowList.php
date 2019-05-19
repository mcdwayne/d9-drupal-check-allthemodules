<?php
/**
 * @file
 * Contains \Drupal\userqueue\Controller\UserQueueShowList.
 */

namespace Drupal\userqueue\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Element\Link;

/**
 * Provides route responses for the commerce module.
 */
class UserQueueShowList extends ControllerBase {

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
  public function AdminUserQueueShowList() {



    $header = array(
      array(
        'data' => t('User Name'),
        'field' => 'uq.title',
        'sort' => 'asc'
      ),
      
	 array(
		'data' => t('Picture')
        	),		


      array('data' => t('Operation'),'colspan' => '3'),
    

      array(
        'data' => t('Position'),
        'field' => 'uq.position'
      ));
    
	$current_path = \Drupal::service('path.current')->getPath();
        $path_args = explode('/', $current_path);   

	$query = $this->database->select('userqueue_user', 'uq')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
      
    $query->fields('uq',array('uid','weight'));
    $query->condition('uqid',$path_args['4']);
    $results = $query
      ->limit(50)
      ->execute();

    $queues = array();
    foreach ($results as $result) {
      $queues[$result->uid] = $result;
    } 

    $rows = array();	



	foreach ($queues as $q) {
        
     $account = \Drupal\user\Entity\User::load($q->uid); 
     $name = $account->getDisplayName();

	 $checking = $account->user_picture->first();

	if(is_object($checking))
		$row_user = $account->user_picture->first()->view('large');
	else
		$row_user = NULL;

	

      $rows[] = array(
        'data' => array(
          t($name),
	  \Drupal::service('renderer')->render($row_user),  
 	   
 	    \Drupal::l(t('Remove'), new Url('userqueue.admin_userqueue.uqid.remove.uid', array('uqid' => $path_args['4'],'uid' => $q->uid))),
	  \Drupal::l(t('Send_To_Top'), new Url('userqueue.admin_userqueue.uqid.top.uid', array('uqid' => $path_args['4'],'uid' => $q->uid))), 
     \Drupal::l(t('Send_To_Bottom'), new Url('userqueue.admin_userqueue.uqid.bottom.uid', array('uqid' => $path_args['4'],'uid' => $q->uid))), 	
	   t($q->weight)		
        )
      );
    }

    $build['admin_userqueue_list_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'admin-userqueue-list', 'class' => array('admin-userqueue')),
      '#empty' => $this->t('No user in this queue.'),
    );

    $build['admin_userqueue_list_pager'] = array('#theme' => 'pager');

    return $build;
  }
}

