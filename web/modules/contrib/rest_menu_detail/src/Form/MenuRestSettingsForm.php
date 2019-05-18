<?php

namespace Drupal\rest_menu_detail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MenuRestSettingsForm.
 */
class MenuRestSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'rest_menu_detail.menurestsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_rest_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('rest_menu_detail.menurestsettings');
    $form['select_parameters'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select parameters'),
      '#description' => $this->t('Select parameters that should be part of your REST response (Menu Title and URI will be part of Response by default)'),
      '#options' => [
        'alias' => $this->t('Alias'),
        'external' => $this->t('External'),
        'absolute_url' => $this->t('Absolute Url'),
        'weight' => $this->t('Weight'),
        'expanded' => $this->t('Expanded'),
        'enabled' => $this->t('Enabled'),
        'uuid' => $this->t('UUID'),
      ],
      '#default_value' => $config->get('select_parameters'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('rest_menu_detail.menurestsettings')
      ->set('select_parameters', $form_state->getValue('select_parameters'))
      ->save();
  }

}
