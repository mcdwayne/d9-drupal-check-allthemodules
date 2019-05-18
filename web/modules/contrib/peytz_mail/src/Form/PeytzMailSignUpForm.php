<?php

namespace Drupal\peytz_mail\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PeytzMailSignUpForm extends PeytzMailSignUpFormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'peytz_mail_sing_up_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('peytz_mail.subscribe_page_settings');
    $config_values = $config->get();
    $configuration = [
      'newsletter_lists' => $config_values['newsletter_lists'],
      'hide_newsletter_lists' => $config_values['lists']['hide_newsletter_lists'],
      'multiple_newsletter_lists' => $config_values['lists']['multiple_newsletter_lists'],
      'header' => $config_values['signup_settings']['header'],
      'intro_text' => $config_values['signup_settings']['intro_text'],
      'name_field_setting' => $config_values['signup_settings']['name_field_setting'],
      'thank_you_page' => $config_values['signup_settings']['thank_you_page'],
      'confirmation_checkbox_text' => $config_values['signup_settings']['confirmation_checkbox_text'],
      'skip_confirm' => $config_values['signup_settings']['skip_confirm'],
      'skip_welcome' => $config_values['signup_settings']['skip_welcome'],
      'ajax_enabled' => $config_values['misc']['ajax_enabled'],
      'subscribe_page_alias' => $config_values['misc']['subscribe_page_alias'],
    ];

    return parent::buildForm($form, $form_state, $configuration);
  }

}
