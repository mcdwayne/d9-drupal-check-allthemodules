<?php

namespace Drupal\field_validation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the FieldValidation constraint.
 */
class FieldValidationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $ruleset_name = $constraint->ruleset_name;
	$rule_uuid = $constraint->rule_uuid;
	$ruleset = \Drupal::entityManager()->getStorage('field_validation_rule_set')->load($ruleset_name);
	if(empty($ruleset)){
	  return;
	}
	//$rule = $ruleset->getFieldValidationRule($rule_uuid);
	$rules = $ruleset->getFieldValidationRules();
	$rules_available = array();
	$field_name = $items->getFieldDefinition()->getName();
	//drupal_set_message($field_name);
	foreach($rules as $rule){
	  if($rule->getFieldName() == $field_name){
	    $rules_available[] = $rule;
	  }
	
	}
	if(empty($rules_available)){
	  return;
	}	
	//drupal_set_message($ruleset_name);
	//drupal_set_message($rule_uuid);
	//drupal_set_message('count:' . count($rules_available));
	$params = array();
	$params['items'] = $items;
	//$params['rule'] = $rule;
	$params['context'] = $this->context;
	foreach($items as $delta => $item){
	  $validator_manager = \Drupal::service('plugin.manager.field_validation.field_validation_rule');
    // You can hard code configuration or you load from settings.
	  foreach($rules_available as $rule) {
      $column = $rule->getColumn();
      $value = $item->{$column};
      $params['value'] = $value;
      $params['delta'] = $delta;
      $config = [];
      $params['rule'] = $rule;
      $plugin_validator = $validator_manager->createInstance($rule->getPluginId(), $config);
      $plugin_validator->validate($params);
	  }
	   
	}

  }
}
