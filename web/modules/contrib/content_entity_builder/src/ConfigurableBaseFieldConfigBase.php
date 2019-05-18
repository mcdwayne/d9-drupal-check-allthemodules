<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for configurable BaseFieldConfig.
 *
 * @see \Drupal\content_entity_builder\Annotation\BaseFieldConfig
 * @see \Drupal\content_entity_builder\ConfigurableBaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\BaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\BaseFieldConfigBase
 * @see \Drupal\content_entity_builder\BaseFieldConfigManager
 * @see plugin_api
 */
abstract class ConfigurableBaseFieldConfigBase extends BaseFieldConfigBase implements ConfigurableBaseFieldConfigInterface {

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildDefaultValueForm(array $form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->getFieldName(),
      '#default_value' => $this->getDefaultValue(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitDefaultValueForm(array &$form, FormStateInterface $form_state) {
    $this->setDefaultValue($form_state->getValue('value'));
  }

}
