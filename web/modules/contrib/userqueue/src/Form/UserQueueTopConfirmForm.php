<?php

/**
 * @file
 * Contains \Drupal\userqueue\Form\UserQueueTopConfirmForm.
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
class UserQueueTopConfirmForm extends ConfirmFormBase {

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
    return $this->t('Are you sure you want to send this user to top?');
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
	->condition('uid',$path_args['6'],'=')
        ->condition('uqid', $this->uqid,'=')	
	->execute()->fetchAssoc();

	$c = $query['weight'];
			
	db_update('userqueue_user')    
	->expression('weight','weight + :weight',array(':weight' => '1'))		
 	->condition('weight',$c,'<')       
	->condition('uqid', $this->uqid,'=')
        ->execute();

	db_update('userqueue_user')    
	->fields(array('weight' => '1'))	
        ->condition('uid',$path_args['6'],'=')
        ->condition('uqid', $this->uqid,'=')
        ->execute();


/*
    $this->connection->delete('userqueue_user')
	->condition('uqid', $this->uqid, '=')
	->condition('uid',$path_args['6'],'=')	
	->execute();
    drupal_set_message($this->t('User deleted'));
 
*/
   $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
