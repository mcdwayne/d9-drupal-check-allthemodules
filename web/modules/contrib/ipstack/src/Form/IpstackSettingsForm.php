<?php

namespace Drupal\ipstack\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure ipstack settings for this site.
 */
class IpstackSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipstack_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ipstack.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ipstack Access Key'),
      '#default_value' => $this->config('ipstack.settings')->get('access_key'),
      '#required' => TRUE,
      '#description' => $this->t("Get Access Key by register at
        <a href='@url' rel='nofollow' target='_new'>ipstack.com</a>.",
        ['@url' => 'https://ipstack.com']
      ),
    ];

    $form['use_https'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use HTTPS'),
      '#default_value' => !empty($this->config('ipstack.settings')->get('use_https')),
      '#description' => $this->t('Connect to the API via HTTPS. For premium plans only.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('ipstack.settings')
      ->set('access_key', $form_state->getValue('access_key'))
      ->set('use_https', $form_state->getValue('use_https'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
