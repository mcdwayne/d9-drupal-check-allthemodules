<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VariableDataComparison.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "variable_data_comparison",
 *   label = @Translation("Variable Data Comparison"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Compare two variable values."),
 *   isContextDependent = FALSE,
 * )
 */
class VariableDataComparison extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $condition) {
    // Only show settings form if the item is already saved.
    if ($condition->isNew()) {
      return [];
    }

    $settings['value_1'] = [
      '#type'          => 'textfield',
      '#title'         => t('Value 1'),
      '#required'      => TRUE,
      '#description'   => t('The value 1 to compare the value.
      <br>To use variables, just type the variable machine name as {{variable_id}}. If the variable is an Entity Variable, you can access the fields values using {{variable_id->field}}'),
      '#default_value' => $condition->getSettings('value_1'),
    ];

    $settings['operator'] = [
      '#type'          => 'select',
      '#required'      => TRUE,
      '#title'         => t('Operator'),
      '#description'   => t('The operation to be performed on this data comparison.'),
      '#default_value' => $condition->getSettings('operator'),
      '#options'       => $this->util->getCriteriaMetOperatorsOptions(),
    ];

    $settings['value_2'] = [
      '#title'         => t('Value 2'),
      '#default_value' => $condition->getSettings('value_2'),
      '#required'      => TRUE,
      '#type'          => 'textarea',
      '#description'   => t('For multiple values comparison, include one per line. 
        It will return TRUE if at least one element was found.
        <br>If the first value in this comparison is a list of values, enter the element(s) id(s)
        <br>Enter the element(s) id(s), one per line.
        <br>To use variables, just type the variable machine name as {{variable_id}}. If the variable is an Entity Variable, you can access the fields values using {{variable_id->field}}'),
      '#prefix'        => '<div id="value_to_compare-wrapper">',
      '#suffix'        => '</div>',
    ];

    return $settings;
  }

  /**
   * Performs the form validation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $item */
    $item = $form_state->getFormObject()->getEntity();
    if (!$item->isNew()) {
      $textarea_fields  = ['contains', '==', 'starts_with', 'ends_with', '!='];
      $value_to_compare = $form_state->getValue('value_to_compare');
      $operator         = $form_state->getValue('operator');
      if (!in_array($operator, $textarea_fields) && stristr($value_to_compare, chr(10))) {
        $form_state->setErrorByName('value_to_compare', t('This operator only allows one value in one line. Please remove the additional lines.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {

    $value_1         = $condition->getSettings('value_1');
    $value_2         = $condition->getSettings('value_2');
    $operator        = $condition->getSettings('operator');
    $event_variables = $event->getArgument('variables');

    // Process values variables.
    $value_1 = $this->processVariables($value_1, $event_variables);
    $value_2 = $this->processVariables($value_2, $event_variables);

    // Remove tags, trim and to lowercase.
    $value_1 = strip_tags(strtolower(trim($value_1)));
    $value_2 = strip_tags(strtolower(trim($value_2)));

    return $this->util->criteriaMet($value_1, $operator, $value_2);

  }

}
