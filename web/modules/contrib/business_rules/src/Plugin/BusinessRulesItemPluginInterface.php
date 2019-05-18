<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\VariablesSet;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface BusinessRulesItemInterface.
 *
 * @package Drupal\business_rules\Plugin
 */
interface BusinessRulesItemPluginInterface extends PluginInspectionInterface {

  const VARIABLE_REGEX = '{{((\w+|\w+\[\d+\]|\w+\[\d+\]\-\>+\w+)|(\w+\-\>+\w+|\w\[\d+\]|\w\[\d+\]\-\>+\w+)+?)}}';

  /**
   * Form constructor.
   *
   * Give a chance to plugin to change the buildForm method.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function buildForm(array &$form, FormStateInterface $form_state);

  /**
   * Provide a description of the item.
   *
   * @return string
   *   A string description of the item.
   */
  public function getDescription();

  /**
   * Get the redirect url for the item edit-form route.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The business rule item.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getEditUrl(ItemInterface $item);

  /**
   * Provide the group of the item.
   *
   * @return string
   *   The item group name
   */
  public function getGroup();

  /**
   * Get the redirect url for the item collection route.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item go get the Url.
   *
   * @return \Drupal\Core\Url
   *   The Url.
   */
  public function getRedirectUrl(ItemInterface $item);

  /**
   * Return the form array.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\business_rules\ItemInterface $item
   *   The configured item.
   *
   * @return array
   *   The render array for the settings form.
   *
   * @internal param array $form
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item);

  /**
   * Return a variable set with all used variables on the item.
   *
   * If you are using variables in a textfield, you can use the regex as the
   * following:
   *
   *  preg_match_all(BusinessRulesItemPluginInterface::VARIABLE_REGEX, $text,
   * $variables);
   *
   * The $variables array will be filled with all used variables at index [1]
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The business rule item.
   *
   * @return \Drupal\business_rules\VariablesSet
   *   The variableSet with all variables used on the Item.
   */
  public function getVariables(ItemInterface $item);

  /**
   * Extract the variables from the plugin settings.
   *
   * @param string $string
   *   The string that can have the variable token: {{variable_id}}.
   *
   * @return array
   *   Array with the variables names.
   *
   * @throws \Exception
   */
  public function pregMatch($string);

  /**
   * Process the item settings before it's saved.
   *
   * @param array $settings
   *   The settings to be processed before save the Business Rule Item.
   * @param \Drupal\business_rules\ItemInterface $item
   *   The item being processed.
   *
   * @return array
   *   The processed settings.
   */
  public function processSettings(array $settings, ItemInterface $item);

  /**
   * Process the tokens on the settings property for the item.
   *
   * @param \Drupal\business_rules\ItemInterface $item
   *   The Business Rules item.
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The BusinessRulesEvent that triggered the processor.
   */
  public function processTokens(ItemInterface &$item, BusinessRulesEvent $event);

  /**
   * Process the item replacing the variables by it's values.
   *
   * @param mixed $content
   *   The item to be replaced by the variable value.
   * @param \Drupal\business_rules\VariablesSet $event_variables
   *   Array of Variables provided by the event.
   *
   * @return mixed
   *   The processed content, replacing the variables tokens for it's values.
   */
  public function processVariables($content, VariablesSet $event_variables);

  /**
   * Plugin form validator.
   *
   * If the plugin needs to perform a form validation, override this function.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

}
