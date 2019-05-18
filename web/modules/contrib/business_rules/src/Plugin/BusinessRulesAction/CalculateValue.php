<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CalculateValue.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "calculate_value",
 *   label = @Translation("Calculate a value"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Calculate a numeric variable value. To composite a string value, use the 'Custom value' variable type."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CalculateValue extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['variable'] = [
      '#type'          => 'select',
      '#title'         => t('Variable to store the result'),
      '#options'       => $this->util->getVariablesOptions(['custom_value_variable']),
      '#default_value' => $item->getSettings('variable'),
      '#required'      => TRUE,
      '#description'   => t('The variable to store the value. Only variables type "Custom value" are allowed.'),
    ];

    $settings['formula'] = [
      '#type'          => 'textarea',
      '#title'         => t('Formula'),
      '#default_value' => $item->getSettings('formula'),
      '#description'   => $this->getFormulaDescription(),
    ];

    return $settings;
  }

  /**
   * Get the formula description.
   *
   * @return array
   *   The render array.
   */
  private function getFormulaDescription() {

    $rows[] = [
      'example' => '{{variable_a}} + {{variable_b}}',
      'name'    => t('Addition'),
      'result'  => t('Sum of {{variable_a}} and {{variable_b}}'),
    ];

    $rows[] = [
      'example' => '{{variable_a}} - {{variable_b}}',
      'name'    => t('Subtraction'),
      'result'  => t('Difference of {{variable_a}} and {{variable_b}}'),
    ];

    $rows[] = [
      'example' => '{{variable_a}} * {{variable_b}}',
      'name'    => t('Multiplication'),
      'result'  => t('Product of {{variable_a}} and {{variable_b}}'),
    ];

    $rows[] = [
      'example' => '{{variable_a}} / {{variable_b}}',
      'name'    => t('Division'),
      'result'  => t('Quotient of {{variable_a}} and {{variable_b}}'),
    ];

    $rows[] = [
      'example' => '{{variable_a}} % {{variable_b}}',
      'name'    => t('Modulo'),
      'result'  => t('Remainder of {{variable_a}} divided by {{variable_b}}'),
    ];

    $rows[] = [
      'example' => '{{variable_a}} ** {{variable_b}}',
      'name'    => t('Exponentiation'),
      'result'  => t('Result of raising {{variable_a}} to the {{variable_b}}. PHP 5.6 and over.'),
    ];

    $rows[] = [
      'example' => '({{variable_a}} + {{variable_b}}) + {{variable_c}}',
      'name'    => t('Parenthesis'),
      'result'  => t('Change the precedence of the mathematical equation.'),
    ];

    $table = [
      '#type'   => 'table',
      '#header' => [
        'example' => t('Example'),
        'name'    => t('Name'),
        'result'  => t('Result'),
      ],
      '#rows'   => $rows,
    ];

    $output['help'] = [
      '#type'      => 'details',
      '#title'     => t('Formula helper'),
      '#collapsed' => TRUE,
    ];

    $output['help']['content'] = $table;

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet = parent::getVariables($item);
    $variableObj = new VariableObject($item->getSettings('variable'), NULL, 'custom_value_variable');
    $variableSet->append($variableObj);

    return $variableSet;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    /** @var \Drupal\business_rules\VariablesSet $event_variables */
    $event_variables = $event->getArgument('variables');
    $raw_formula     = $action->getSettings('formula');
    $formula         = $this->processVariables($raw_formula, $event_variables);
    $variable        = $action->getSettings('variable');

    // Check if formula is safe.
    $allowed_values   = str_split('()+-*/% ');
    $allowed_values[] = chr(10);
    $allowed_values[] = '\r';
    $allowed_values[] = '\n';
    if (is_numeric(str_replace($allowed_values, '', $formula))) {
      try {
        $formula_result = eval('return ' . $formula . ';');
        $success        = TRUE;
      }
      catch (\Exception $e) {
        $formula_result = NULL;
        $success        = FALSE;
      }
    }
    else {
      $formula_result = $formula;
      $success        = FALSE;
    }

    $event_variables->replaceValue($variable, $formula_result);

    if ($success) {
      $result = [
        '#type'   => 'markup',
        '#markup' => t('Formula "%raw_formula" transformed into "%formula" with the result: "%result" assigned to variable "%variable".', [
          '%raw_formula' => $raw_formula,
          '%formula'     => $formula,
          '%result'      => $formula_result,
        ]),
      ];
    }
    else {
      $result = [
        '#type'   => 'markup',
        '#markup' => t('The expression: "%raw_formula" processed as "%formula" could not be evaluated. Please, make sure it is a valid numeric expression.', [
          '%raw_formula' => $raw_formula,
          '%formula'     => $formula,
        ]),
      ];
    }

    return $result;
  }

}
