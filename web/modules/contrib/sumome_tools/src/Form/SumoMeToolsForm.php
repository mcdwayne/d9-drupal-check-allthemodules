<?php

namespace Drupal\sumome_tools\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to configure SumoMe.
 */
class SumoMeToolsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sumome_tools_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('sumome_tools.settings');

    // Page title field.
    $form['sumo-site-id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('SumoMe Site ID:'),
      '#default_value' => $config->get('sumome_tools.sumo_site_id'),
      '#description' => $this->t('Enter the Site ID you received from SumoMe.com.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sumome_tools.settings');
    $config->set('sumome_tools.sumo_site_id', $form_state->getValue('sumo-site-id'));
    $config->save();
    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sumome_tools.settings',
    ];
  }

}
