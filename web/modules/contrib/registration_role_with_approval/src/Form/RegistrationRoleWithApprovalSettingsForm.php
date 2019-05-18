<?php


namespace Drupal\registration_role_with_approval\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class RegistrationRoleWithApprovalSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'registration_role_with_approval_setting_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $system_roles = user_roles(TRUE);
    $config = \Drupal::config('registration_role_with_approval.settings');

    $mailing_list = $this->config('registration_role_with_approval.settings')->get('mailing_list');
    if(!$mailing_list){
      $mailing_list = $this->config('system.site')->get('mail');
    }
    $email_subject = $config->get('email_subject');
    $email_body = $config->get('email_body');
    $profile_roles = $config->get('profile_roles');

    $form['roles'] = array(
      '#type' => 'fieldset',
      '#title' => t('Available Roles on registration form'),
      '#collapsible' => TRUE,
    );

    foreach($system_roles as $system_role){
      $role_id = $system_role->id();
      if($role_id !='0' && $role_id != 'authenticated'){
        $form['roles'][$system_role->id()] = array(
          '#type' => 'checkbox',
          '#title' => t($system_role->label()),
          '#default_value' => $profile_roles[$system_role->id()]['default'],
        );
        $form['roles'][$system_role->id()."needs_approval"] = array(
          '#type' => 'checkbox',
          '#title' => t('needs approval'),
          '#states' => array(
            'invisible' => array(
              ":input[name='$role_id']" => array('checked' => FALSE),
            )
          ),
          '#attributes' => array(
            'style' => 'margin-left: 2em',
          ),
          '#default_value' => $profile_roles[$system_role->id()]['needs_approval'],
        );
      }
    }

    $form['custom_mail'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom registration email configuration'),
      '#collapsible' => TRUE,
    );

    $form['custom_mail']['mailing_list'] = array(
      "#type" => "textarea",
      "#title" => "Mailing list",
      "#default_value" => $mailing_list,
      "#description" => "Enter email address (if multiple addresses separate with space) to whom email is sent when user approval is needed!",
    );
    $form['custom_mail']['email_subject'] = array(
      "#type" => "textfield",
      "#title" => "Email subject",
      "#default_value" => $email_subject,
    );
    $form['custom_mail']['email_body'] = array(
      "#type" => "textarea",
      "#title" => "Email body",
      "#default_value" => $email_body,
    );

    return parent::buildForm($form, $form_state);
  }

  public function getEditableConfigNames() {
    return ['registration_role_with_approval.settings'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state){
    $system_roles = user_roles($membersonly = TRUE);
    $config_list = array();
    foreach($system_roles as $system_role){
      if($form_state->getValue($system_role->id()) == 1){
        $config_list[$system_role->id()]['id'] = $system_role->id();
        $config_list[$system_role->id()]['default'] = 1;
        $config_list[$system_role->id()]['label'] = $system_role->label();
        $config_list[$system_role->id()]['needs_approval'] = $form_state->getValue($system_role->id()."needs_approval");
      };
    }
    $this->config('registration_role_with_approval.settings')
      ->set('profile_roles', $config_list);
    $this->config('registration_role_with_approval.settings')
      ->set('mailing_list', $form_state->getValue('mailing_list'));
    $this->config('registration_role_with_approval.settings')
      ->set('email_subject', $form_state->getValue('email_subject'));
    $this->config('registration_role_with_approval.settings')
      ->set('email_body', $form_state->getValue('email_body'));
    $this->config('registration_role_with_approval.settings')->save();
    parent::submitForm($form, $form_state);

  }
}

?>