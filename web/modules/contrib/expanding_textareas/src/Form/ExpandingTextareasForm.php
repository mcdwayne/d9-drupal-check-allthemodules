<?php

namespace Drupal\expanding_textareas\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ExpandingTextareasForm.
 *
 * @package Drupal\expanding_textareas\Form
 */
class ExpandingTextareasForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'expanding_textareas.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'expanding_textareas_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default = 'edit-expanding-textareas-admin-fields';
    $config = $this->config('expanding_textareas.settings');
    $form['expanding_textareas_admin_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enabled Textareas'),
      '#description' => t('Add one textarea id per line (not including # sign) or use * to trigger for all textareas.'),
      '#default_value' => $config->get('expanding_textareas_admin_fields') ? $config->get('expanding_textareas_admin_fields') : 'edit-expanding-textareas-admin-fields',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('expanding_textareas.settings')
      ->set('expanding_textareas_admin_fields', $form_state->getValue('expanding_textareas_admin_fields'))
      ->save();
  }

}
