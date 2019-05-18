<?php

namespace Drupal\sms_rule_based\Plugin;

/**
 * Interface for SMS routing rule plugins.
 */
interface SmsRoutingRulePluginInterface {

  /**
   * Returns the machine name of the plugin instance.
   */
  public function getName();

  /**
   * Returns the label of the plugin instance.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the description of the plugin instance.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Gets the form widget for this rule type.
   *
   * @return array
   *   Form API array containing the widget for this rule type.
   */
  public function getWidget();

  /**
   * Gets whether this rule is enabled or not.
   *
   * @return bool
   */
  public function isEnabled();

  /**
   * Gets the operator for this rule.
   *
   * @return string
   */
  public function getOperator();

  /**
   * Gets the operand for this rule.
   * @return string
   */
  public function getOperand();

  /**
   * Gets the human-readable description of the operator.
   *
   * @return string
   */
  public function getReadableOperator();

  /**
   * Gets the human-readable operand value.
   *
   * @return string
   */
  public function getReadableOperand();

  /**
   * Gets whether this rule is negated or not.
   *
   * @return bool
   */
  public function isNegated();

  /**
   * Gets the type of plugin.
   *
   * @return string
   */
  public function getType();

  /**
   * Returns the sub-set of numbers that match this rule.
   *
   * @param array $numbers
   *   An array of numbers to be matched.
   * @param array $context
   *   An array of named parameters which are needed by this rule to evaluate.
   *
   * @return array
   *   The sub-set of $numbers that match the rule.
   */
  public function match(array $numbers, array $context);

  /**
   * Renders this rule in a human-understandable format.
   *
   * @return array
   *   A render array containing the markup for display.
   */
  public function render();

  /**
   * Processes the form widget submitted value into a string for storage.
   *
   * @param mixed $widget_value
   *   The form value returned from the widget.
   *
   * @return string
   *   The processed form value.
   */
  public function processWidgetValue($widget_value);

}
