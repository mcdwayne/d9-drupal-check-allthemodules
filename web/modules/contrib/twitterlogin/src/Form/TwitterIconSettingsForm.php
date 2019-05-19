<?php

namespace Drupal\twitterlogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social API Icon Twitter.
 */
class TwitterIconSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_login_twitter_icon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twitterlogin.icon.settings',
    ];
  }

  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twitterlogin.icon.settings');

    $path = drupal_get_path('module', 'twitterlogin');

    $display1 = '<img src = "/' . $path . '/images/sign-in-with-twitter.png" border="0">';
    $display2 = '<img src = "/' . $path . '/images/twitter-icon.png" border="0">';

    $form['icon']['display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Display Settings'),
      '#default_value' => $config->get('display'),
      '#options' => [0 => $display1, 1 => $display2],
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
    $this->config('twitterlogin.icon.settings')
      ->set('display', $values['display'])
      ->set('display_url', $values['display_url'])
      ->save();

    drupal_set_message($this->t('Icon Settings are updated'));
  }

}
