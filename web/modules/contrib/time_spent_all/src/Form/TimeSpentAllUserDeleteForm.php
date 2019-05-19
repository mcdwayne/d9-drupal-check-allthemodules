<?php

/**
 * @file
 * Contains \Drupal\system\Form\RssFeedsForm.
 */

namespace Drupal\time_spent_all\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Request;

class TimeSpentAllUserDeleteForm extends FormBase implements ContainerInjectionInterface {

  protected $database;

  protected $entityManager;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('database')
    );
  }

  /**
   * Constructs a new CommentForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, Connection $database) {
    $this->entityManager = $entity_manager;
    $this->database = $database;
  }

  function getFormId() {
    return 'time_spent_all_user_delete_form';
  }

  function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form = array();

    $form['ip'] = array(
      '#title' => t('Enter IP Address'),
      '#type' => 'textfield', 
    );

    $form['date_api_test'] = array(
      '#title' => t('Date Api Test'),
      '#type' => 'date_select',
    );
   $form['clearalldata'] = array(
      '#type' => 'checkbox',
      '#title' => t('Clear all IP\'s data'),
      '#description' => t('Warning it will clear all data from db'), 
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Delete data'),
    );
 
    
    return $form;
  }

  function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $userInputValues = $form_state->getUserInput();
    $ip = $userInputValues['ip'];
    $clearalldata = $userInputValues['clearalldata'];
    
if($clearalldata == 1){
   

  $result1 = db_truncate('time_spent_all_page')->execute();
  $result2 = db_truncate('time_spent_all_site')->execute(); 

    drupal_set_message("all data deleted time_spent_all_page,time_spent_all_site");

}
else{
  $num_deleted = db_delete('time_spent_all_page')
  ->condition('ip', $ip)
  ->execute();
   $num_deleted2 = db_delete('time_spent_all_site')
  ->condition('ip', $ip)
  ->execute();
  drupal_set_message("deleted for time_spent_all_page table = ".$num_deleted." time_spent_all_site = ".$num_deleted2." ".$clearalldata );
}
    //$account = array_shift($accounts);



   // $redirect = new RedirectResponse($base_url.'/admin/reports/time_spent_all/timespent-list-users/'.$account->id());
   // $redirect->send();
  }
}
