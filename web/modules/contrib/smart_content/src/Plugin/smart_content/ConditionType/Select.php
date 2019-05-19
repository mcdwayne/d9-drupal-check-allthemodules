<?php

namespace Drupal\smart_content\Plugin\smart_content\ConditionType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\ConditionType\ConditionTypeBase;

/**
 * Provides a 'number' ConditionType.
 *
 * @SmartConditionType(
 *  id = "select",
 *  label = @Translation("Select"),
 * )
 */
class Select extends ConditionTypeBase {

  //@todo: confirm working as intended.

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $condition_definition = $this->conditionInstance->getPluginDefinition();
    //@todo: need to get this working.
    $options = [];
    $form['label'] = [
      '#type' => 'container',
      //@todo: get condition group name from group
      '#markup' => $condition_definition['label'] . '(' . $condition_definition['group'] . ')',
      '#attributes' => ['class' => ['condition-label']],
    ];
    $form['value'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => isset($this->configuration['value']) ? $this->configuration['value'] : $this->defaultFieldConfiguration()['value'],
    ];
    return $form;
  }

  public function evaluate($values, $context) {
    if (isset($values['value'], $context)) {
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFieldConfiguration() {
    return [
      'value' => '',
    ];
  }

  function getLibraries() {
    return ['smart_content/condition_type.standard'];
  }

  public function getAttachedSettings() {
    return $this->getConfiguration() + $this->defaultFieldConfiguration();
  }
}
