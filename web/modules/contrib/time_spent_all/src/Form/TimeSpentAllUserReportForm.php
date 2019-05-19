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

class TimeSpentAllUserReportForm extends FormBase implements ContainerInjectionInterface {

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
    return 'time_spent_all_user_report_form';
  }

  function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
     
    $form = array();

    $form['ip'] = array(
      '#title' => t('IP'),
      '#type' => 'textfield', 
    );

    $form['date_api_test'] = array(
      '#title' => t('Date Api Test'),
      '#type' => 'date_select',
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('See Report'),
    );
    
    return $form;
  }

  function submitForm(array &$form, FormStateInterface $form_state) {
    global $base_url;
    $userInputValues = $form_state->getUserInput();
    //$accounts = $this->entityManager->getStorage('user')->loadByProperties(array('name' => $userInputValues['username']));
   // $account = array_shift($accounts);
    //$e_ip = str_replace(':', '-', $userInputValues['ip']);
     //$e_ip = str_replace('.', '-', $e_ip);
    $redirect = new RedirectResponse($base_url.'/admin/reports/time_spent_all/timespent-list-users?ip='.$userInputValues['ip']);
    $redirect->send();
     
  }
}
