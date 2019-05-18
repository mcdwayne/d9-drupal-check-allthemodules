<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * DateRangeFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "date_range_field_validation_rule",
 *   label = @Translation("Date range"),
 *   description = @Translation("Validates user-entered text against a specified date range.")
 * )
 */
class DateRangeFieldValidationRule extends ConfigurableFieldValidationRuleBase {

  /**
   * {@inheritdoc}
   */
   
  public function addFieldValidationRule(FieldValidationRuleSetInterface $field_validation_rule_set) {

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'field_validation_rule_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'min' => NULL,
	  'max' => NULL,
	  'cycle' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['cycle'] = array(
      '#title' => $this->t('Cycle of date'),
      '#description' => $this->t('Specify the cycle of date, support: global, year, month, week, day, hour, minute.'),
      '#type' => 'select',
      '#options' => array(
        'global' => $this->t('Global'),
        'year' => $this->t('Year'),
        'month' => $this->t('Month'),
        'week' => $this->t('Week'),
        'day' => $this->t('Day'),
        'hour' => $this->t('Hour'),
        'minute' => $this->t('Minute'),
      ),  
      '#default_value' => $this->configuration['cycle'],
    );  
    $form['min'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Minimum date'),
	    '#description' => $this->t('Optionally specify the minimum date.'),
      '#default_value' => $this->configuration['min'],
    );
    $form['max'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum date'),
	    '#description' => $this->t('Optionally specify the maximum date.'),
      '#default_value' => $this->configuration['max'],
    );
	/*
    $form['help'] = array(
      '#markup' => t('For minimum and maximum time, we only support date format "Y-m-d H:i:s", here is the relation between cycle and minimum/maximum date format:')
      . theme('item_list', array('items' => array(t('global - [Y-m-d H:i:s]'), t('year - [m-d H:i:s]'), t('month - [d H:i:s]'), t('week - [w H:i:s]'), t('day - [H:i:s]'), t('hour - [i:s]'), t('minute - [s]'))))
      . t('If cycle is "global", it support more date formats which could be converted through strtotime(), such as "now", "+1 month", "+1 day", it also support "value + 1 day", "value2 - 1 day", at here "value" means start date of user input, "value2" means end date'),
    );	
	*/
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['min'] = $form_state->getValue('min');
	$this->configuration['max'] = $form_state->getValue('max');
	$this->configuration['cycle'] = $form_state->getValue('cycle');
  }
  
  public function validate($params) {
    $value = isset($params['value']) ? $params['value'] : '';
	$rule = isset($params['rule']) ? $params['rule'] : null;
	$context = isset($params['context']) ? $params['context'] : null;
	$settings = array();
	if(!empty($rule) && !empty($rule->configuration)){
	  $settings = $rule->configuration;
	}
	//$settings = $this->rule->settings;
    if ($value !== '' && !is_null($value)) {
      $flag = FALSE;
      //$settings =  $this->rule->settings;
      $cycle = isset($settings['cycle']) ? $settings['cycle'] : '';
      // support date, datetime
      if (!is_numeric($value)) {
        $value = strtotime($value);
      }

      $date_str = date("Y-m-d H:i:s", $value);
      if ($cycle =='global') {

        if (!empty($settings['min'])) {
          //$settings['min'] = $settings['min'];
          $settings['min'] = strtotime($settings['min']);
          $settings['min'] = date("Y-m-d H:i:s", $settings['min']);
        }
        if (!empty($settings['max'])) {
          //$settings['max'] = strtr($settings['max'], $tokens);
          $settings['max'] = strtotime($settings['max']);
          $settings['max'] = date("Y-m-d H:i:s", $settings['max']);
        }
      }
      if ($cycle =='year') {
        $date_str = substr($date_str, 5);
      }
      elseif ($cycle =='month') {
        $date_str = substr($date_str, 8);
      }
      elseif ($cycle =='week') {
        $week_day = date('w', strtotime($date_str));
        $date_str = substr($date_str, 10);
        $date_str = $week_day . $date_str;
      }
      elseif ($cycle =='day') {
        $date_str = substr($date_str, 11);
      }
      elseif ($cycle =='hour') {
        $date_str = substr($date_str, 14);
      }
      elseif ($cycle =='minute') {
        $date_str = substr($date_str, 17);
      }

      if (!empty($settings['min'])  && $date_str < $settings['min']) {
        $flag = TRUE;
      }
      if (!empty($settings['max'])  && $date_str > $settings['max']) {
        $flag = TRUE;
      }

      if ($flag) {
        $context->addViolation($rule->getErrorMessage());
      }      

    }	
    //return true;
  }
}
