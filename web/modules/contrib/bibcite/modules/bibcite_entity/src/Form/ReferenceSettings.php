<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Common Reference settings.
 */
class ReferenceSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bibcite_entity.reference.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_entity_reference_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config_modes = $this->configFactory->listAll('core.entity_view_mode.bibcite_reference');
    $modes = ['default' => $this->t('Default')];
    foreach ($config_modes as $config_name) {
      $mode = $this->configFactory->getEditable($config_name);
      // Get substring 'view_mode_name' from 'bibcite_reference.view_mode_name'
      // id string. String 'bibcite_reference.' contains 18 symbols.
      $name = substr($mode->get('id'), 18);
      $modes[$name] = $this->t($mode->get('label'));
    }
    $config = $this->config('bibcite_entity.reference.settings');

    $form['view_mode'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reference page view mode'),
      '#tree' => TRUE,
      'reference_page_view_mode' => [
        '#type' => 'select',
        '#options' => $modes,
        '#title' => $this->t('Reference page view mode'),
        '#description' => $this->t('View mode which is used for rendering reference entities on their own pages.'),
        '#default_value' => $config->get('display_override.reference_page_view_mode'),
      ],
    ];

    $form['ui_override'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Interface override'),
      '#tree' => TRUE,
      'enable_form_override' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Override entity forms'),
        '#description' => $this->t("Regroup all base fields of Reference entity to vertical tabs. You can use it if you don't want to configure form display view."),
        '#default_value' => $config->get('ui_override.enable_form_override'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_entity.reference.settings');
    $config->set('display_override.reference_page_view_mode', $form_state->getValue(['view_mode', 'reference_page_view_mode']));
    $config->set('ui_override.enable_form_override', (bool) $form_state->getValue(['ui_override', 'enable_form_override']));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
