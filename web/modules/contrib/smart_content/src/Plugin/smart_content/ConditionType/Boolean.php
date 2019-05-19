<?php

namespace Drupal\smart_content\Plugin\smart_content\ConditionType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_content\Condition\ConditionBase;
use Drupal\smart_content\ConditionType\ConditionTypeBase;

/**
 * Provides a 'number' ConditionType.
 *
 * @SmartConditionType(
 *  id = "boolean",
 *  label = @Translation("Boolean"),
 * )
 */
class Boolean extends ConditionTypeBase {

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $condition_definition = $this->conditionInstance->getPluginDefinition();
    $form = ConditionBase::attachNegateElement($form, $this->configuration);

    if (!isset($form['#attributes']['class'])) {
      $form['#attributes']['class'] = [];
    }
    $form['#attributes']['class'][] = 'condition-boolean';

    $form['label'] = [
      '#type' => 'container',
      // @todo: get condition group name from group
      '#markup' => $condition_definition['label'] . '(' . $condition_definition['group'] . ')',
      '#attributes' => ['class' => ['condition-label']],
    ];
    return $form;
  }

  /**
   * @inheritdoc
   */
  function defaultFieldConfiguration() {
    return [];
  }

  /**
   * @inheritdoc
   */
  public function evaluate($values, $context) {
    return (bool) $context;
  }

  /**
   * @inheritdoc
   */
  function getLibraries() {
    return ['smart_content/condition_type.standard'];
  }


  public function getAttachedSettings() {
    return $this->getConfiguration() + $this->defaultFieldConfiguration();
  }
}