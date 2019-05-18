<?php

namespace Drupal\google_standout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GoogleStandoutSettings implements settings form.
 */
class GoogleStandoutSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_standout.settings'];
  }

  /**
   * Get the form_id.
   *
   * @inheritDoc
   */
  public function getFormId() {
    return 'google_standout_form_setting';
  }

  /**
   * Build the Form.
   *
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $config = $this->config('google_standout.settings');
    foreach (node_type_get_names() as $key => $value) {
      $standouts[$key] = ucwords($value);
    }
    $default_content_types = $config->get('google_standout');
    $form['google_standout'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Content type'),
      '#description' => $this->t('Choosing content type will enable the standout tag for their pages.'),
      '#options' => $standouts,
      '#default_value' => $default_content_types,
    ];

    $google_standout_config = $config->get('google_standout_config');
    $form['google_standout_config'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select configuration type'),
      '#description' => $this->t('Choosing Configuration type allow user to add tag as a link or meta for their pages.'),
      '#options' => ['Link', 'Meta'],
      '#default_value' => $google_standout_config,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Add submit handler.
   *
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_input_values = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('google_standout.settings');
    $config->set('google_standout', $user_input_values['google_standout']);
    $config->set('google_standout_config', $user_input_values['google_standout_config']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
