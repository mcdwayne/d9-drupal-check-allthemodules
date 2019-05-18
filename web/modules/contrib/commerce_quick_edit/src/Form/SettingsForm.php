<?php

namespace Drupal\commerce_quick_edit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures commerce quick edit.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_quick_edit_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_quick_edit.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_quick_edit.settings');

    $form['modal_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal width'),
      '#required' => TRUE,
      '#description' => $this->t('Modal form width. Value must end with px or % (e.g. 800px or 90%).'),
      '#default_value' => $config->get('modal_width') ? $config->get('modal_width') : '800px',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('commerce_quick_edit.settings');
    $config->set('modal_width', $values['modal_width']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
