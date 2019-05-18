<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DataComparison.
 *
 * @package Drupal\business_rules\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "data_comparison",
 *   label = @Translation("Data Comparison"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Compare entity field value against one value."),
 *   isContextDependent = TRUE,
 *   reactsOnIds = {},
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class DataComparison extends BusinessRulesConditionPlugin {

  const CURRENT_DATA = 'current_data';
  const ORIGINAL_DATA = 'original_data';

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $condition) {
    // Only show settings form if the item is already saved.
    if ($condition->isNew()) {
      return [];
    }

    $settings['data_to_compare'] = [
      '#type' => 'select',
      '#title' => t('Data to compare'),
      '#required' => TRUE,
      '#options' => [
        '' => t('- Select -'),
        self::CURRENT_DATA => t('Current value'),
        self::ORIGINAL_DATA => t('Original value'),
      ],
      '#description' => t('Current value is the value that is being saved.') . '<br>' . t('Original value is the previous saved value.'),
      '#default_value' => empty($condition->getSettings('data_to_compare')) ? '' : $condition->getSettings('data_to_compare'),
    ];

    $settings['operator'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Operator'),
      '#description' => t('The operation to be performed on this data comparison.'),
      '#default_value' => $condition->getSettings('operator'),
      '#options' => $this->util->getCriteriaMetOperatorsOptions(),
    ];

    $settings['value_to_compare'] = [
      '#title' => t('Value to compare'),
      '#default_value' => $condition->getSettings('value_to_compare'),
      '#type' => 'textarea',
      '#description' => t('For multiple values comparison, include one per line. 
        It will return TRUE if at least one element was found.
        <br>If the comparison field is a list of values, enter the element(s) id(s)
        <br>Enter the element(s) id(s), one per line.
        <br>To use variables, just type the variable machine name as {{variable_id}}. If the variable is an Entity Variable, you can access the fields values using {{variable_id->field}}'),
      '#states'        => [
        'invisible' => [
          'select[name="operator"]' => ['value' => 'empty'],
        ],
      ],
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
    $item = $form_state->getFormObject()->getEntity();
    if (!$item->isNew()) {
      $textarea_fields = ['contains', '==', 'starts_with', 'ends_with', '!='];
      $value_to_compare = $form_state->getValue('value_to_compare');
      $operator = $form_state->getValue('operator');
      if (!in_array($operator, $textarea_fields) && stristr($value_to_compare, chr(10))) {
        $form_state->setErrorByName('value_to_compare', t('This operator only allows one value in one line. Please remove the additional lines.'));
      }
      elseif ($operator != 'empty' && trim($value_to_compare) == '') {
        $form_state->setErrorByName('value_to_compare', t('This operator require one value to be compared with.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    /** @var \Drupal\Core\Entity\Entity $entity */
    $field = $condition->getSettings('field');
    $operator = $condition->getSettings('operator');
    $variables = $event->getArgument('variables');
    $values_to_compare = explode(chr(10), $condition->getSettings('value_to_compare'));
    $values_to_compare = $this->processInternalVariables($values_to_compare, $variables);
    $data_to_compare = $condition->getSettings('data_to_compare');

    if ($data_to_compare == self::CURRENT_DATA) {
      $entity = $event->getArgument('entity');
    }
    elseif ($data_to_compare == self::ORIGINAL_DATA) {
      $entity = $event->getArgument('entity_unchanged');
    }
    $values = $entity->get($field)->getValue();

    if (count($values)) {
      foreach ($values as $value) {
        foreach ($values_to_compare as $compare) {
          return $this->compare($value, $compare, $operator);
        }
      }
    }
    elseif ($operator == 'empty') {
      foreach ($values_to_compare as $compare) {
        return $this->compare(['value' => ''], $compare, $operator);
      }
    }

    return FALSE;
  }

  /**
   * Compare 2 values.
   *
   * @param mixed $value
   *   The First value.
   * @param mixed $compare
   *   The second value.
   * @param string $operator
   *   The operator.
   *
   * @return bool
   *   The comparison result.
   */
  private function compare($value, $compare, $operator) {
    if (isset($value['value'])) {
      $entity_value = strip_tags(strtolower(trim($value['value'])));
      $compare_value = strtolower(trim($compare));

      return $this->util->criteriaMet($entity_value, $operator, $compare_value);
    }
    elseif (isset($value['target_id'])) {
      $entity_value = strip_tags(strtolower(trim($value['target_id'])));
      $compare_value = strtolower(trim($compare));

      return $this->util->criteriaMet($entity_value, $operator, $compare_value);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Process the item replacing the variables by it's values.
   *
   * @param mixed $values_to_compare
   *   The item to be replaced by the variable value.
   * @param \Drupal\business_rules\VariablesSet $variables
   *   Array of Variables provided by the event.
   *
   * @return mixed
   *   The processed content, replacing the variables tokens for it's values.
   */
  public function processInternalVariables($values_to_compare, VariablesSet $variables) {

    /** @var \Drupal\business_rules\VariableObject $variable */
    if ($variables->count()) {

      foreach ($variables->getVariables() as $variable) {
        if (!is_array($variable->getValue())) {
          foreach ($values_to_compare as $key => $value_to_compare) {
            $value = $variable->getValue();
            if (is_string($value) || is_numeric($value)) {
              $values_to_compare[$key] = str_replace('{{' . $variable->getId() . '}}', $value, $value_to_compare);
            }
          }
        }
        else {
          foreach ($values_to_compare as $key => $value_to_compare) {

            foreach ($variable->getValue() as $item) {
              // Add each value from variable array.
              $values_to_compare[] = $item;
            }

            // Unset the variable id from $values_to_compare.
            $variable_key = array_keys($values_to_compare, '{{' . $variable->getId() . '}}');
            foreach ($variable_key as $key) {
              unset($values_to_compare[$key]);
            }
          }
        }
      }

    }

    return $values_to_compare;
  }

}
