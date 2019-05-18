<?php

namespace Drupal\entity_parser\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_parser.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_parser.config');
    $value_alias = $config->get('default_hook_alias');
    $form['default_hook_alias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Hook Alias'),
      '#description' => $this->t('Insert here the default hook alias for entity_parser that can add ONLY ONE TIME (Cannot be editable) , current default : ers_451e8e6d7b53f8a06e3f8517cf02b856 '),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $value_alias,
    ];
    if ($value_alias && $value_alias != "") {
      $form['default_hook_alias']['#attributes'] = ['readonly' => 'readonly'];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('entity_parser.config')
      ->set('default_hook_alias', $form_state->getValue('default_hook_alias'))
      ->save();
  }

}
