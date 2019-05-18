<?php

namespace Drupal\cmood\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CmoodSettingsForm implements a config form for cmood admin settings.
 */
class CmoodSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmood_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form = [];
    $form['node_type'] = [
      '#type' => 'fieldset',
      '#title' => t('Content types'),
      '#description' => t('Choose the content type for which you want to enable Cmood.'),
    ];

    $form['node_type']['cmood_enabled_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Calculate mood for content types:'),
      '#default_value' => \Drupal::config('cmood.settings')
        ->get('cmood_enabled_types'),
      '#options' => node_type_get_names(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cmood.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('cmood.settings');
    $config->set('cmood_enabled_types', $userInputValues['cmood_enabled_types']);
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
