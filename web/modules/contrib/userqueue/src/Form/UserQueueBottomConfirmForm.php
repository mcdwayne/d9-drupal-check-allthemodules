<?php

/**
 * @file
 * Contains \Drupal\userqueue\Form\UserQueueBottomConfirmForm.
 */
namespace Drupal\userqueue\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before deleting userqueue.
 */
class UserQueueBottomConfirmForm extends ConfirmFormBase {

  /**
   * The UQID of the item to delete.
   *
   * @var string
   */
  protected $uqid;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new UserQueueDeletConfirmForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'userqueue_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $queue = userqueue_load($this->uqid);
    return $this->t('Are you sure you want to send this user to bottom?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() { 
	
    return new Url('userqueue.admin_userqueue.uqid.show',array('uqid' =>$this->uqid));
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $uqid = NULL) {
    $this->uqid = $uqid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

 	 $current_path = \Drupal::service('path.current')->getPath();
    	 $path_args = explode('/', $current_path);	    
 	
         $queue = userqueue_load($this->uqid);

	$query = db_select('userqueue_user')
	->fields('userqueue_user',array('weight'))	
	->condition('uid', $path_args['6'],'=')
        ->condition('uqid', $this->uqid,'=')	
	->execute()->fetchassoc();

	$c = $query['weight'];
	
	
	
	$results = db_select('userqueue_user')
		->fields(NULL, array('weight'))
		->condition('uqid',$this->uqid,'=')
		->execute()->fetchAll();

	$d = count($results);
		

	db_update('userqueue_user')    
	->expression('weight','weight - :weight',array(':weight' => 1))	
 	->condition('weight', $c,'>')       
	->condition('uqid', $this->uqid,'=')
        ->execute();

	db_update('userqueue_user')    
	->fields(array('weight' => $d))	
        ->condition('uid',$path_args['6'],'=')
        ->condition('uqid', $this->uqid,'=')
        ->execute();	

   $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
