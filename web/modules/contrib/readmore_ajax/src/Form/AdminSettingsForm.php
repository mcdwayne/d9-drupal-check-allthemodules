<?php

/**
 * @file
 * Contains Drupal\readmore_ajax\Form\AdminSettingsForm.
 */

namespace Drupal\readmore_ajax\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AdminSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'readmore_ajax.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'readmore_ajax_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('readmore_ajax.adminsettings');

    $form['readmore_ajax_node_types'] = [
      '#title' => t('Content types'),
      '#type' => 'checkboxes',
      '#description' => t('Select node types you want to activate ajax readmore on. If you select nothing, AJAX will be enabled everywhere.'),
      '#default_value' =>  (array) $config->get('readmore_ajax_node_types'),
      '#options' => node_type_get_names(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('readmore_ajax.adminsettings')
      ->set('readmore_ajax_node_types', $form_state->getValue('readmore_ajax_node_types'))
      ->save();

  }
}