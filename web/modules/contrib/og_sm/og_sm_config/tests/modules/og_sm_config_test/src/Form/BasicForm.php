<?php

namespace Drupal\og_sm_config_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og_sm_config\Form\SiteConfigFormBase;

/**
 * Configure site information settings for this site.
 */
class BasicForm extends SiteConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_sm.basic_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.site'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');

    $form['site_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Site details'),
      '#open' => TRUE,
    ];
    $form['site_information']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('name', $form_state->getValue('site_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
