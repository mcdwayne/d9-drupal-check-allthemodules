<?php

namespace Drupal\popup_after_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Use this class to create configuration form for module.
 */
class PopupAfterLoginConfiguration extends ConfigFormBase {

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'popup_after_login_config';
  }

  /**
   * Create configurations Name.
   */
  protected function getEditableConfigNames() {
    return [
      'popup_after_login_config.settings',
    ];
  }

  /**
   * Create form for configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $config = $this->config('popup_after_login_config.settings');
    $form['popup_after_login_choose_role'] = [
      '#type' => 'checkboxes',
      '#options' => $roles,
      '#title' => $this->t("Select role."),
      '#default_value' => $config->get('popup_after_login_choose_role') ? $config->get('popup_after_login_choose_role') : NULL,
      '#description' => $this->t('Select one of the above role to enable popup message.'),
    ];
    $form['group_first_time'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Details for 'First time popup after login'"),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['group_first_time']['popup_after_login_first_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('popup_after_login_first_title') ? $config->get('popup_after_login_first_title') : '',
      '#size' => 60,
      '#maxlength' => 200,
      '#description' => $this->t("Title for 'After first time login' popup. Leave it blank if you want to disable this popup."),
    ];
    $form['group_first_time']['popup_after_login_first_message'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('popup_after_login_first_message') ? $config->get('popup_after_login_first_message') : '',
      '#description' => $this->t("Write message to show  'After first time login' popup."),
    ];
    $form['popup_after_login_group_always'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Details for 'Always show popup after login'"),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['popup_after_login_group_always']['popup_after_login_first_title_always'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('popup_after_login_first_title_always') ? $config->get('popup_after_login_first_title_always') : '',
      '#size' => 60,
      '#maxlength' => 200,
      '#description' => $this->t("Title for 'Always show popup after login'.  Leave it blank if you want to disable this popup."),
    ];
    $form['popup_after_login_group_always']['popup_after_login_first_message_always'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#title' => $this->t('Message'),
      '#default_value' => $config->get('popup_after_login_first_message_always') ? $config->get('popup_after_login_first_message_always') : '',
      '#description' => $this->t("Write message for 'Always show popup after login'."),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit popup after login configurations.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('popup_after_login_config.settings')
      ->set('popup_after_login_choose_role', $form_state->getValue('popup_after_login_choose_role'))
      ->set('popup_after_login_first_title', $form_state->getValue('popup_after_login_first_title'))
      ->set('popup_after_login_first_message', $form_state->getValue('popup_after_login_first_message')['value'])
      ->set('popup_after_login_first_title_always', $form_state->getValue('popup_after_login_first_title_always'))
      ->set('popup_after_login_first_message_always', $form_state->getValue('popup_after_login_first_message_always')['value'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
