<?php

namespace Drupal\personacontent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Persona Content settings for this site.
 */
class PersonaContentAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personacontent_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['personacontent.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('personacontent.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['debug'] = [
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('Enable/Disable Debug on Screen for Region.'),
      '#title' => $this->t('Debug on Screen for REGION'),
      '#type' => 'checkbox',
    ];

    $form['general']['debug_html'] = [
      '#default_value' => $config->get('debug_html'),
      '#description' => $this->t('Enable/Disable Debug on Screen for HTML.'),
      '#title' => $this->t('Debug on Screen for HTML'),
      '#type' => 'checkbox',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('personacontent.settings');
    $config
      ->set('debug', $form_state->getValue('debug'))
      ->set('debug_html', $form_state->getValue('debug_html'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
