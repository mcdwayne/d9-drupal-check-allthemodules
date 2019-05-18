<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Class CompareNumberOfResultsBetweenTwoViews.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "compare_results_of_two_views",
 *   label = @Translation("Compare the number of results between two views"),
 *   group = @Translation("Views"),
 *   description = @Translation("Compare the number of results between two views."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CompareNumberOfResultsBetweenTwoViews extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    // Only present the settings form if the condition is already saved.
    if ($item->isNew()) {
      return [];
    }

    $settings['view_1'] = [
      '#type'          => 'select',
      '#title'         => t('First view. View name : Display mode id : Display mode title.'),
      '#options'       => $this->util->getViewsOptions(),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('view_1'),
      '#description'   => t('Select the view to compare the number of results.'),
    ];

    $settings['arguments_1'] = [
      '#type'          => 'textarea',
      '#title'         => t('Arguments for view 1'),
      '#description'   => t('Any argument the first view may need, one per line. Be aware of including them at same order as the CONTEXTUAL FILTERS configured in the view. You may use variables.'),
      '#default_value' => $item->getSettings('arguments_1'),
    ];

    $settings['operator'] = [
      '#type'          => 'select',
      '#title'         => t('Comparator'),
      '#description'   => t('The operator to compare the result between the views.'),
      '#required'      => TRUE,
      '#options'       => [
        '==' => '=',
        '>'  => '>',
        '>=' => '>=',
        '<'  => '<',
        '<=' => '<=',
        '!=' => '!=',
      ],
      '#default_value' => $item->getSettings('operator'),
    ];

    $settings['view_2'] = [
      '#type'          => 'select',
      '#title'         => t('Second view. View name : Display mode id : Display mode title.'),
      '#options'       => $this->util->getViewsOptions(),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('view_2'),
      '#description'   => t('Select the view to compare the number of results.'),
    ];

    $settings['arguments_2'] = [
      '#type'          => 'textarea',
      '#title'         => t('Arguments for view 2'),
      '#description'   => t('Any argument the second view may need, one per line. Be aware of including them at same order as the CONTEXTUAL FILTERS configured in the view. You may use variables.'),
      '#default_value' => $item->getSettings('arguments_2'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    // Get settings.
    $defined_view1   = $condition->getSettings('view_1');
    $args1           = $condition->getSettings('arguments_1');
    $operator        = $condition->getSettings('operator');
    $defined_view2   = $condition->getSettings('view_2');
    $args2           = $condition->getSettings('arguments_2');
    $event_variables = $event->getArgument('variables');

    // Process settings.
    $defined_view1 = explode(':', $defined_view1);
    $view_id1      = $defined_view1[0];
    $display1      = $defined_view1[1];

    $defined_view2 = explode(':', $defined_view2);
    $view_id2      = $defined_view2[0];
    $display2      = $defined_view2[1];

    $args1 = explode(chr(10), $args1);
    $args1 = array_map('trim', $args1);
    $args1 = array_filter($args1, 'strlen');

    $args2 = explode(chr(10), $args2);
    $args2 = array_map('trim', $args2);
    $args2 = array_filter($args2, 'strlen');

    // Process variables.
    foreach ($args1 as $key => $value) {
      $args1[$key] = $this->processVariables($value, $event_variables);
    }
    foreach ($args2 as $key => $value) {
      $args2[$key] = $this->processVariables($value, $event_variables);
    }

    // Execute view 1.
    $view1 = Views::getView($view_id1);
    $view1->setArguments($args1);
    $view1->setDisplay($display1);
    $view1->preExecute();
    $view1->build();
    if ($view1->execute()) {
      $view_result1 = $view1->result;
      $result1      = count($view_result1);
    }
    else {
      $result1 = 0;
    }

    // Execute view 2.
    $view2 = Views::getView($view_id2);
    $view2->setArguments($args2);
    $view2->setDisplay($display2);
    $view2->preExecute();
    $view2->build();
    if ($view2->execute()) {
      $view_result2 = $view2->result;
      $result2      = count($view_result2);
    }
    else {
      $result2 = 0;
    }

    // Check the condition.
    if ($this->util->criteriaMet($result1, $operator, $result2)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
