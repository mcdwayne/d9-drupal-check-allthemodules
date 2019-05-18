<?php

namespace Drupal\page_menu_reorder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PageMenuReoderForm.
 */
class ReorderMenuSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'page_menu_reorder.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reorder_menu_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $config = $this->config('page_menu_reorder.settings');
    $form['reordermenu_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('The content types to enable reorder menu functionality'),
      '#default_value' => !empty($config->get('enabled_types')) ? $config->get('enabled_types') : [],
      '#options' => $types,
      '#description' => t('On the specified node types, reorder menu tab will be available'),
    ];
    $form['array_filter'] = ['#type' => 'value', '#value' => TRUE];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_types = array_filter($form_state->getValue('reordermenu_types'));
    sort($enabled_types);
    $this->config('page_menu_reorder.settings')
      ->set('enabled_types', $enabled_types)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
