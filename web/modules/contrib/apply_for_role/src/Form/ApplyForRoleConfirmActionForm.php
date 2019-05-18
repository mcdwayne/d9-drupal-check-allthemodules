<?php
/**
 * @FILE:
 *
 * Contains a form for confirming an apply/delete action on an application.
 */

namespace Drupal\apply_for_role\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\apply_for_role\application_manager;
use Drupal\Core\Url;

class ApplyForRoleConfirmActionForm extends ConfirmFormBase{
  private $application_manager;
  private $form_action;
  private $application;
  private $roles;
  private $username;

  /**
   * Constructor. Loads application manager and Determines if this is an approval or denial confirmation.
   */
  public function __construct(){
    $this->application_manager = new application_manager();

    $current_uri = \Drupal::request()->getRequestUri();
    // Determine whether this is for approval or denial.
    if(strpos($current_uri, '/admin/people/role-applications/approve') !== FALSE){
      $this->form_action = 'approve';
    }elseif (strpos($current_uri, '/admin/people/role-applications/deny') !== FALSE){
      $this->form_action = 'deny';
    }
  }

  // Returns the form ID.
  public function getFormId()
  {
    return 'apply_for_role_confirm_action_form';
  }

  /**
   * Returns what the confirmation button will say.
   */
  public function getConfirmText() {
    if($this->form_action == 'approve'){
      return $this->t('Approve Application');
    }
    else{
      return $this->t('Deny Application');
    }
  }

  /**
   * Creates the question for the form. Essentially page title.
   */
  public function getQuestion() {
    if($this->form_action == 'approve'){
      return $this->t('Approve Application');
    }
    else{
      return $this->t('Deny Application');
    }
  }

  /**
   * Creates the description for the cancelation form.
   */
  public function getDescription() {
    if($this->form_action == 'approve'){
      return $this->t('By approving this application, the user %username above will receive the role(s) %role.',
        array(
          '%role' => $this->roles,
          '%username' => $this->username,
        ));
    }
    else{
      return $this->t('By denying this application, the user %username will NOT receive the role(s) %role and their application will be marked as denied.',
        array(
          '%role' => $this->roles,
          '%username' => $this->username,
        ));
    }
  }

  /**
   * Provides the URL to go to if the user cancels this action.
   */
  public function getCancelUrl() {
    //this needs to be a valid route otherwise the cancel link won't appear
    return new Url('apply_for_role.applications_listing');
  }

  /**
   * Form builder. Takes AID, populates some base values.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $aid = NULL)
  {
    if(!$this->application = $this->application_manager->get_application($aid)){
      // Some one is passing invalid application ID's.
      return $this->redirect('apply_for_role.applications_listing');
    }
    if($this->application->get('status') != 0){
      // Some one is passing already active application ID's.
      return $this->redirect('apply_for_role.applications_listing');
    }

    $this->roles = $this->application_manager->rids_to_text($this->application->get('rids'));
    $this->username = $this->application_manager->display_username_for_application($this->application);

    return parent::buildForm($form, $form_state);
  }

  // Determines what the cancellation text should be on the form.
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * Form submission handler
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // If the form is valid and submitted, either approve or deny the application based on where the form came from.
    if($this->form_action == 'approve'){
      $this->application_manager->approve_application($this->application);
      $form_state->setRedirect('apply_for_role.applications_listing');
    }
    else{
      $this->application_manager->deny_application($this->application);
      $form_state->setRedirect('apply_for_role.applications_listing');
    }
  }
}
