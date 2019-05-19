<?php

namespace Drupal\smart_content\Plugin\smart_content\ConditionType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\ConditionBase;
use Drupal\smart_content\ConditionType\ConditionTypeBase;

/**
 * Provides a 'number' ConditionType.
 *
 * @SmartConditionType(
 *  id = "number",
 *  label = @Translation("Number"),
 * )
 */
class Number extends ConditionTypeBase {

  //@todo: add format_options
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $condition_definition = $this->conditionInstance->getPluginDefinition();
    $form = ConditionBase::attachNegateElement($form, $this->configuration);

    $form['label'] = [
      '#type' => 'container',
      //@todo: get condition group name from group
      '#markup' => $condition_definition['label'] . '(' . $condition_definition['group'] . ')',
      '#attributes' => ['class' => ['condition-label']],
    ];
    $form['op'] = [
      '#type' => 'select',
      '#options' => $this->getOperators(),
      '#default_value' => isset($this->configuration['op']) ? $this->configuration['op'] : $this->defaultFieldConfiguration()['op'],
    ];
    $form['value'] = [
      '#type' => 'number',
      '#required' => TRUE,
      '#default_value' => isset($this->configuration['value']) ? $this->configuration['value'] : $this->defaultFieldConfiguration()['value'],
    ];
    if(isset($condition_definition['format_options']['prefix'])) {
      $form['value']['#prefix'] = $condition_definition['format_options']['prefix'];
    }
    if(isset($condition_definition['format_options']['suffix'])) {
      $form['value']['#suffix'] = $condition_definition['format_options']['suffix'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFieldConfiguration() {
    return [
      'op' => 'equals',
      'value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators() {
    return [
      'equals' => '=',
      'gt' => '>',
      'lt' => '<',
      'gte' => '>=',
      'lte' => '<=',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate($values, $context) {
    if (isset($values['value'], $values['op'], $context)) {
      switch ($values['op']) {
        case 'equals':
          return $context === $values['value'];

        case 'gt':
          return $context > $values['value'];

        case 'lt':
          return $context < $values['value'];

        case 'gte':
          return $context >= $values['value'];

        case 'lte':
          return $context <= $values['value'];
      }
    }

    return FALSE;
  }


  function getLibraries() {
    return ['smart_content/condition_type.standard'];
  }

  public function getAttachedSettings() {
    return $this->getConfiguration() + $this->defaultFieldConfiguration();
  }
}
