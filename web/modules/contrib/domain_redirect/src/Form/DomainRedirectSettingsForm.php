<?php

/**
 * @file
 * Contains \Drupal\domain_redirect\Form\DomainRedirectSettingsForm
 */

namespace Drupal\domain_redirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DomainRedirectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_redirect_settings_form';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_redirect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_redirect.settings');
    $form['domain_redirect_destination_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination domain'),
      '#default_value' => $config->get('destination_domain'),
      '#description' => $this->t('All domain redirects will redirect to this domain.'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain_redirect.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'domain_redirect_') !== FALSE) {
        $config->set(str_replace('domain_redirect_', '', $key), $value);
      }
    }
    $config->save();
    drupal_set_message($this->t('The configuration was saved.'));
  }
}
