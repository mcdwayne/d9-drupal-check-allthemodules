<?php

namespace Drupal\background_process_ass\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Implements BackgroundProcessAssSettingsForm Class.
 */
class BackgroundProcessAssSettingsForm extends ConfigFormBase {

  /**
   * Implements to Get Form ID.
   */
  public function getFormId() {
    return 'background_process_ass_settings_form';
  }

  /**
   * Implements Form Submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('background_process_ass.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Implements to Get Editable Config Name from Config Files.
   */
  protected function getEditableConfigNames() {
    return ['background_process_ass.settings'];
  }

  /**
   * Implements to Build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['background_process_ass_max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max age'),
      '#description' => $this->t('Time in seconds to wait before considering a process dead.'),
      '#default_value' => \Drupal::config('background_process_ass.settings')->get('background_process_ass_max_age'),
    ];
    return parent::buildForm($form, $form_state);
  }

}
