<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Class CheckViewResultCount.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "check_views_result_count",
 *   label = @Translation("Check the number of results returned by a view"),
 *   group = @Translation("Views"),
 *   description = @Translation("Compare the number of results returned by a view against a defined number."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CheckViewResultCount extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['view'] = [
      '#type'          => 'select',
      '#title'         => t('View to execute. View name : Display mode id : Display mode title.'),
      '#options'       => $this->util->getViewsOptions(),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('view'),
      '#description'   => t('Select the view to compare the number of results.'),
    ];

    $settings['arguments'] = [
      '#type'          => 'textarea',
      '#title'         => t('Arguments'),
      '#description'   => t('Any argument the view may need, one per line. Be aware of including them at same order as the CONTEXTUAL FILTERS configured in the view. You may use variables.'),
      '#default_value' => $item->getSettings('arguments'),
    ];

    $settings['comparison'] = [
      '#type'          => 'number',
      '#title'         => t('Minimum number of results'),
      '#description'   => t('The condition will return true if the view has at least the given number of results.'),
      '#min'           => 0,
      '#default_value' => $item->getSettings('comparison'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    // Get settings.
    $defined_view    = $condition->getSettings('view');
    $args            = $condition->getSettings('arguments');
    $comparison      = $condition->getSettings('comparison');
    $event_variables = $event->getArgument('variables');

    // Process settings.
    $defined_view = explode(':', $defined_view);
    $view_id      = $defined_view[0];
    $display      = $defined_view[1];

    $args = explode(chr(10), $args);
    $args = array_map('trim', $args);
    $args = array_filter($args, 'strlen');

    // Process variables.
    foreach ($args as $key => $value) {
      $args[$key] = $this->processVariables($value, $event_variables);
    }

    // Execute view.
    $view = Views::getView($view_id);
    $view->setArguments($args);
    $view->setDisplay($display);
    $view->preExecute();
    $view->build();
    if ($view->execute()) {
      $view_result = $view->result;
      $result      = count($view_result);
    }
    else {
      $result = 0;
    }

    // Check the condition.
    if ($result >= $comparison) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
