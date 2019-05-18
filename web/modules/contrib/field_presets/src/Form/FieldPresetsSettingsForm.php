<?php
/**
 * @file
 * Contains \Drupal\field_presets\Form\FieldPresetsSettingsForm.
 */

namespace Drupal\field_presets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure limit text module.
 */
class FieldPresetsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_presets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['field_presets.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('field_presets.settings');

    $form['form_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on form display'),
      '#description' => $this->t('Show the add field using preset local task on the default form display.'),
      '#default_value' => $config->get('form_display'),
    ];

    $form['redirect_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect to default form display'),
      '#description' => $this->t('On creation of a field, redirect to the default form display.'),
      '#default_value' => $config->get('redirect_default'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('field_presets.settings')
      ->set('form_display', $values['form_display'])
      ->set('redirect_default', $values['redirect_default'])
      ->save();

    drupal_set_message('Settings have been updated.');
  }

}
