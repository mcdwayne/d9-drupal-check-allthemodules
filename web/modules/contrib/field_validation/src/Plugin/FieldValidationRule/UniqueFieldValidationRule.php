<?php

namespace Drupal\field_validation\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * UniqueFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "unique_field_validation_rule",
 *   label = @Translation("Unique"),
 *   description = @Translation("Verifies that all values are unique in current entity or bundle.")
 * )
 */
class UniqueFieldValidationRule extends ConfigurableFieldValidationRuleBase {

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
      'scope' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['scope'] = array(
      '#title' => $this->t('Scope of unique'),
      '#description' => $this->t('Specify the scope of unique values, support: entity, bundle.'),
      '#type' => 'select',
      '#options' => array(
        'entity' => $this->t('Entity'),
        'bundle' => $this->t('Bundle'),
      ),
      '#default_value' => $this->configuration['scope'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['scope'] = $form_state->getValue('scope');
  }
  
  public function validate($params) {
    $value = isset($params['value']) ? $params['value'] : '';
	$rule = isset($params['rule']) ? $params['rule'] : null;
	$context = isset($params['context']) ? $params['context'] : null;
	$items = isset($params['items']) ? $params['items'] : array();
	$delta = isset($params['delta']) ? $params['delta'] : '';
	$column = $rule->getColumn();

	
	$settings = array();
	if(!empty($rule) && !empty($rule->configuration)){
	  $settings = $rule->configuration;
	}
    $flag = TRUE;
    $scope = isset($settings['scope']) ? $settings['scope'] : '';
    $count = 0;
    foreach ($items as $delta1 => $item1) {
      if ($delta != $delta1) {
        if ($value == $item1->{$column}) {
          $flag = FALSE;
          break;
        }
      }
    }
    if ($flag) {
      $entity = $items->getEntity();
      $entity_type_id = $entity->getEntityTypeId();	
	  
	  $query = \Drupal::entityQuery($entity_type_id);

      if ($scope == 'bundle') {

	    $bundle = $entity->bundle();
        $bundle_key = $entity->getEntityType()->getKey('bundle');
        /*		
	    $bundle_keys = array(
		  "node" => "type",
		  "taxonomy_term" => "vid",
		  "comment" => "comment_type",
		  "block_content" => "type",  
		);
		*/
		if(!empty($bundle_key)){
          $query->condition($bundle_key, $bundle);
		}

      }
	  
	  $id_key = $entity->getEntityType()->getKey('id');
	  $query->condition($id_key, (int) $items->getEntity()->id(), '<>');
	  
	  $field_name = $items->getFieldDefinition()->getName();
	  
	  if(!empty($column)){
	    $field_name = $field_name . '.' . $column;
	  }
	  $query->condition($field_name, $value);
	  
	  $count = $query->range(0, 1)
        ->count()
        ->execute();

      if ($count) {
        $flag = FALSE;

      }

    }

    if (!$flag) {
      $context->addViolation($rule->getErrorMessage());
    }	
	
    //return true;
  }
}
