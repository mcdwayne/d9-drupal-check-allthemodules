<?php

/**
 * @file
 * Contains \Drupal\wisski_core\Plugin\views\argument\StringArgument.
 *
 */

namespace Drupal\wisski_core\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\StringArgument as ViewsString;

/**
 * Numeric argument for fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("wisski_string")
 */
class StringArgument extends ViewsString {
  
  private $comparisonOperator = 'IN';

  public function defineOptions() {
    $options = parent::defineOptions();
    $options['comparison_operator'] = ['default' => 'IN'];
    return $options;
  }


  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $operators = [];
    foreach ($this->operatorInfo() as $op => $info) {
      $operators[$op] = $info['label'];
    }
    $form['comparison_operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison operator'),
      '#default_value' => $this->options['comparison_operator'],
      '#options' => $operators,
      '#group' => 'options][more',
    ];
    
  }


  /** 
   * 
   */
  protected function prepareValue() {
    // this is taken from Drupal\views\Plugin\views\argument\StringArgument::query()
    // It's strange that there is no function wrapper for that, as it is
    // reused oftentimes
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }

    if (!empty($this->options['break_phrase'])) {
      $this->unpackArgumentValue();
    }
    else {
      $this->value = [$argument];
      $this->operator = 'or';
    }

  }

  /** Provide info for available comparison operators.
   * 
   * Structure is as follows:
   *
   * [
   *   '<comparison operator>' => [
   *     'label' => '<label>', // the label
   *     'multi_or operator' => '...',  // optional: comparison operator to use to make one condition with array of all values with logical OR operator
   *     'multi_and operator' => '...',  // optional: same for AND operator
   *     'single operator' => '...',  // optional: for each of the values one condition will be created and the conditions will be group according to the AND/OR operator
   *   ],
   *   ...
   * ]
   */
  protected function operatorInfo() {
    
    $map = [
      'IN' => [
        'label' => $this->t('One of / Equals'),
        'multi_or operator' => 'IN',
        'single operator' => '=',
      ],
      'CONTAINS' => [
        'label' => $this->t('Contains'),
      ],
      'STARTS_WITH' => [
        'label' => $this->t('Starts with'),
      ],
      'ENDS_WITH' => [
        'label' => $this->t('Ends with'),
      ],
      '<' => [
        'label' => $this->t('Is less than'),
      ],
      '>' => [
        'label' => $this->t('Is greater than'),
      ],

    ];
    return $map;

  }


  /**
   * {@inheritdoc}
   */
  public function query($group_by = false) {
    // note that $this->value may not be set already, we have to set it here
    $this->prepareValue();
    // make sure the value member always holds an array for ease of use
    if (!is_array($this->value)) {
      $this->value = [$this->value];
    }
    // determine whether we really have multple values
    $is_multiple = count($this->value) > 1;
    // if multiple mode, we have to distinguish by logical operator
    $mode = $is_multiple ? 'multi_' . $this->operator : 'single';
    // the overall/abstract operator may define deviating comparison operators
    // for the single value and multiple AND/OR values.
    // if we have multiple values but there is no special comparison operator
    // for the logical operator, then the condition building behaves like in
    // single mode
    $comparison_operator = $this->options['comparison_operator'];
    $operator_info = $this->operatorInfo()[$comparison_operator];
    if (isset($operator_info["$mode operator"])) {
      $comparison_operator = $operator_info["$mode operator"];
    }
    else {
      // single mode now means that we pass a single value to the query 
      // condition. if there are multiple values, we create multiple conditions
      // combined by the given logical operator
      $mode = 'single';
    }

    $field = isset($this->configuration['wisski_field']) ? $this->configuration['wisski_field'] : $this->realField;
    if ($mode == 'single') {
      // we are in single value mode, i.e. we pass one single value to the
      // comparison operator. in case of multiple values we create multiple
      // conditions
      $cond = $this->query->query;
      if ($is_multiple && $this->operator == 'or') {
        $cond = $cond->orConditionGroup();
      }
      foreach ($this->value as $value) {
        $cond->condition($field, $value, $comparison_operator);
      }
    }
    else {
      // we are in multi value mode with a special comparison operator.
      // we pass all the values as one single array to the operator
      $this->query->query->condition($field, $this->value, $comparison_operator);
    }
  }

}

