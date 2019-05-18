<?php

namespace Drupal\bueditor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * BUEditor settings form.
 */
class BUEditorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bueditor_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bueditor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bueditor.settings');
    $form['devmode'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable development mode'),
      '#default_value' => $config->get('devmode'),
      '#description' => t('In development mode minified libraries are replaced by source libraries to make debugging easier.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bueditor.settings');
    // Invalidate library cache if devmode has changed.
    $devmode = $form_state->getValue('devmode');
    if ($config->get('devmode') != $devmode) {
      \Drupal::cache('discovery')->invalidate('library_info');
    }
    // Save config
    $config->set('devmode', $devmode)->save();
    parent::submitForm($form, $form_state);
  }

}
