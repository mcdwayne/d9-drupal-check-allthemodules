<?php

namespace Drupal\single_user_role\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Single User Role configuration settings.
 */
class SingleUserRoleConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_user_role_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('single_user_role.settings');

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
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['single_user_role.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['single_user_role_field_type'] = [
      '#title' => t('Field type'),
      '#description' => t('Set type of field to use for user role selection.'),
      '#type' => 'select',
      '#default_value' => \Drupal::config('single_user_role.settings')->get('single_user_role_field_type'),
      '#options' => [
        'select' => t('Select field'),
        'radios' => t('Radio field'),
      ],
    ];
    $form['single_user_role_field_desc'] = [
      '#title' => 'Role field helptext',
      '#type' => 'textarea',
      '#description' => t('This text is displayed at user role field.'),
      '#default_value' => \Drupal::config('single_user_role.settings')->get('single_user_role_field_desc'),
      '#cols' => 40,
      '#rows' => 4,
    ];
    return parent::buildForm($form, $form_state);
  }

}
