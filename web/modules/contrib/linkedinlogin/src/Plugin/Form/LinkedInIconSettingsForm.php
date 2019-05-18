<?php

namespace Drupal\linkedinlogin\Plugin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social API Icon LinkedIn.
 */
class LinkedInIconSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkedin_oauth_login_icon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'linkedinlogin.icon.settings',
    ];
  }

  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkedinlogin.icon.settings');

    $path = drupal_get_path('module', 'linkedinlogin');

    $display1 = '<img src = "/' . $path . '/images/sign-in-with-linkedin.png" border="0" width="12%">';
    $display2 = '<img src = "/' . $path . '/images/linkedin-logo.png" border="0" width="3%">';
    $display3 = '<img src = "/' . $path . '/images/linkedin-logo-512x512.png" border="0" width="8%">';
    

    $form['icon']['display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display Settings'),
      '#default_value' => $config->get('display'),
      '#options' => [0 => $display1, 1 => $display2, 2 => $display3],
    ];

    $form['icon']['display_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Direct URL'),
      '#default_value' => $config->get('display_url'),
      '#description' => $this->t('Please use absolute URL'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit Common Admin Settings.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('linkedinlogin.icon.settings')
      ->set('display', $values['display'])
      ->set('display_url', $values['display_url'])
      ->save();

    drupal_set_message($this->t('Icon Settings are updated'));
  }

}
