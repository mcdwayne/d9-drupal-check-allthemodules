<?php

/**
 * @FILE:
 * Contains \Drupal\apply_for_role\Form\ApplyForRoleApplicationForm
 *
 * Administrative settings for Google QR Code.
 */

namespace Drupal\apply_for_role\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\apply_for_role\application_manager;


class ApplyForRoleApplicationForm extends FormBase{
  private $application_manager;

  /**
   * Constructor, loads application manager.
   */
  public function __construct()
  {
    $this->application_manager = new application_manager();
  }

  // Form ID.
  public function getFormId()
  {
    return 'apply_for_role_application_form';
  }

  /**
   * Form builder.
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('apply_for_role.settings');
    $available_roles = $config->get('apply_for_role_roles') ? $config->get('apply_for_role_roles') : array();

    foreach($this->what_roles_does_user_have() as $current_user_role){
      if($key_to_remove = array_search($current_user_role, $available_roles)){
        unset($available_roles[$key_to_remove]);
      }
    }


    // Go through all roles, find roles with a value of 0 and unset them
    foreach($available_roles as $available_role_key => $available_role){
      if($available_role === 0){
        unset($available_roles[$available_role_key]);
      }
    }

    // @TODO: Remove roles that the user is currently already applying for?

    if(!empty($available_roles)){

      $form['application_roles'] = array(
        '#title' => t('Role to apply for'),
        '#options' => $available_roles,
        '#required' => TRUE,
      );

      if($config->get('allow_user_message_with_app')){
        $form['application_message'] = array(
          '#title' => 'Application Message',
          '#type' => 'textarea',
        );
      }
      if($config->get('multiple_roles_per_app') && count($available_roles) > 1){
        $form['application_roles']['#type'] = 'checkboxes';
        $form['#description'] = t('Select the role(s) that you wish to apply for.');
        if(isset($form['application_message'])){$form['application_message']['#description'] = t('Optionally, you may provide an explenation of why you wish to be accepted for this/these role(s)');}
      }else{
        $form['application_roles']['#type'] = 'radios';
        $form['#description'] = t('Select the role that you wish to apply for.');
        if(isset($form['application_message'])){$form['application_message']['#description'] = t('Optionally, you may provide an explenation of why you wish to be accepted for this role');}
      }

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Apply for Role'
      );
    }
    else{
      $form['no_roles_available'] = array(
        '#markup' => t('Currently there are no roles available or remaining for application.'),
      );
    }
    return $form;
  }

  /**
   * Form Submission handler
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('apply_for_role.settings');
    $submited_values = $form_state->cleanValues()->getValues();
    $current_user_uid = \Drupal::currentUser()->id();

    // Handle RID's differently based on whether multiple roles per app are allowed.
    if($config->get('multiple_roles_per_app')){
      $rids = $submited_values['application_roles'];
      foreach ($rids as $key => $value){
        if (!$value) {unset($rids[$key]);}
      }
    }else{
      // Just grab the singular value and place it in an array to send onwards!
      $rids = array($submited_values['application_roles']);
    }

    $message = isset($submited_values['application_message']) ? $submited_values['application_message']: NULL;

    // Creat an application with the above gathered information
    $this->application_manager->create_application($current_user_uid, $rids, $message);

    drupal_set_message(t('Thank you for submitting an applicaton. Your requested is currently queued for review.'));
  }

  /**
   * Helper function that fetches the roles a user has.
   *
   * @return array
   *   Array of roles that a user has.
   */
  protected function what_roles_does_user_have(){
    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    // Remove authenticated role.
    unset($roles[0]);

    return $roles;
  }
}
