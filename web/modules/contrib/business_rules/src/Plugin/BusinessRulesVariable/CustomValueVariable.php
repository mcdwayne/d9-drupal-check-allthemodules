<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesItemPluginInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConstantVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "custom_value_variable",
 *   label = @Translation("Custom value"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Set an variable with a constant value."),
 *   reactsOnIds = {},
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CustomValueVariable extends BusinessRulesVariablePlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['value'] = [
      '#type'          => 'textarea',
      '#title'         => t('Custom value'),
      '#description'   => t("The initial value for this variable. You can change this value through actions."),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('value'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {

    $custom_value = $variable->getSettings('value');
    $variables    = $event->getArgument('variables');

    // Search for another's variables inside the original value.
    preg_match_all(BusinessRulesItemPluginInterface::VARIABLE_REGEX, $custom_value, $inside_variables);
    $varObjects = [];
    if (count($inside_variables)) {
      $inside_variables = $inside_variables[1];
      if (count($inside_variables)) {
        foreach ($inside_variables as $inside_variable) {
          $var = Variable::load($inside_variable);

          if ($var instanceof Variable) {
            $varObjects[$var->id()] = $variables->getVariables()[$var->id()];
          }
          // Check if variable already exists.
          elseif (in_array($inside_variable, array_keys($variables->getVariables()))) {
            $varObjects[$inside_variable] = new VariableObject($inside_variable, $variables->getVariables()[$inside_variable]->getValue(), $variables->getVariables()[$inside_variable]->getType());
          }
          // Check if it's a entity variable with the field variable_id->field.
          elseif (stristr($inside_variable, '->')) {
            $arr_temp   = explode('->', $inside_variable);
            $var_name   = $arr_temp[0];
            $field_name = $arr_temp[1];
            $var        = Variable::load($var_name);

            if ($var instanceof Variable) {
              $entity = $variables->getVariables()[$var->id()]->getValue();
              if ($entity instanceof Entity) {
                $field = $entity->get($field_name);
                $value = $field->value;

                $varObjects[$inside_variable] = new VariableObject($var_name, $value, $var->getType());
              }
            }

          }
        }
      }
    }

    // Replace the variables tokens for the variable value.
    if (count($varObjects)) {
      foreach ($varObjects as $key => $var) {
        if (is_string($var->getValue()) || is_numeric($var->getValue())) {
          $custom_value = str_replace('{{' . $key . '}}', $var->getValue(), $custom_value);
        }
      }
    }

    $variableObject = new VariableObject($variable->id(), $custom_value, 'custom_value_variable');

    return $variableObject;
  }

}
