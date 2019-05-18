<?php

/**
 * @file
 * Contains Drupal\ajax_login_popup\Form\RedirectionConfigForm.
 */

namespace Drupal\ajax_login_popup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RedirectionConfigForm.
 *
 * @package Drupal\RedirectionConfigForm\Form
 */
class RedirectionConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'redirection_config_form.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redirection_config_form.settings');
    $form['redirection_details'] = [
      '#type' => 'details',
      '#title' => t('Redirection Details'),
      '#open' => TRUE,
    ];
    $form['redirection_details']['link'] = [
      '#type' => 'textfield',
      '#title' => t('Url for Redirection'),
      '#default_value' => $config->get('link'),
      '#description' => t("Links. Examples: node/1"),
    ];
    $form['redirection_details']['ajax_button'] = [
      '#type' => 'textfield',
      '#title' => t('Button Name'),
      '#default_value' => $config->get('ajax_button'),
      '#description' => t("Add the Button Name which will display on Popup Block"),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('redirection_config_form.settings')
      ->set('link', $form_state->getValue('link'))
	  ->set('ajax_button', $form_state->getValue('ajax_button'))
      ->save();
  }

}
