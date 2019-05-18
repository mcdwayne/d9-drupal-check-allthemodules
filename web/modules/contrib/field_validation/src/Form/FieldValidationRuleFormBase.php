<?php

namespace Drupal\field_validation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleInterface;
use Drupal\field_validation\FieldValidationRuleSetInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Drupal\Core\Entity\FieldStorageConfig;

/**
 * Provides a base form for FieldValidationRule.
 */
abstract class FieldValidationRuleFormBase extends FormBase {

  /**
   * The fieldValidationRuleSet.
   *
   * @var \Drupal\field_validation\FieldValidationRuleSetInterface
   */
  protected $fieldValidationRuleSet;

  /**
   * The fieldValidationRule.
   *
   * @var \Drupal\field_validation\FieldValidationRuleInterface
   */
  protected $fieldValidationRule;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_validation_rule_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\field_validation\FieldValidationRuleSetInterface $field_validation_rule_set
   *   The field_validation_rule_set.
   * @param string $field_validation_rule
   *   The field_validation_rule ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function buildForm(array $form, FormStateInterface $form_state, FieldValidationRuleSetInterface $field_validation_rule_set = NULL, $field_validation_rule = NULL, $field_name='') {
    $this->fieldValidationRuleSet = $field_validation_rule_set;
    try {
      $this->fieldValidationRule = $this->prepareFieldValidationRule($field_validation_rule);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid field_validation_rule id: '$field_validation_rule'.");
    }
    $request = $this->getRequest();

    if (!($this->fieldValidationRule instanceof ConfigurableFieldValidationRuleInterface)) {
      throw new NotFoundHttpException();
    }

    //$form['#attached']['library'][] = 'field_validation/admin';
    $form['uuid'] = array(
      '#type' => 'hidden',
      '#value' => $this->fieldValidationRule->getUuid(),
    );
    $form['id'] = array(
      '#type' => 'hidden',
      '#value' => $this->fieldValidationRule->getPluginId(),
    );
	
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Field Validation Rule title'),
      '#default_value' => $this->fieldValidationRule->getTitle(),
      '#required' => TRUE,
    );
	$entity_type_id = $this->fieldValidationRuleSet->getAttachedEntityType();
	$bundle = $this->fieldValidationRuleSet->getAttachedBundle();
	//$field_options = array();
    $field_options = array(
      '' => $this->t('- Select -'),
    );	
	foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $fieldname => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
		$field_options[$fieldname] = $field_definition->getLabel();
		
		//if($field_name == 'field_test'){
		 // $field_all = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', $field_name);
		 //$field_all = $field_definition->getPropertyDefinitions();
		   //$field_storage_definitions = \Drupal::entityManager()->getFieldStorageDefinitions('node');
		  // $field_all = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', $field_name);
		 // $schema =$field_all->getSchema();
		   //$field_storage = $field_storage_definitions[$field_name]->getPropertyDefinitions();
		   //$field_storage = $field_storage_definitions[$field_name]->getPropertyDefinitions();
		 // drupal_set_message(var_export($schema));
		//}
      }
	}
    $default_field_name = $this->fieldValidationRule->getFieldName();
	if(!empty($field_name)){
	  $default_field_name = $field_name;
	}
	
    $form['field_name'] = array(
      '#type' => 'select',
      '#title' => $this->t('Field name'),
	  '#options' => $field_options,
      '#default_value' => $default_field_name,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::updateColumn',
        'wrapper' => 'edit-field-name-wrapper',
      ),	  
    );
	//$default_field_name = $form_state->getValue('field_name', $field_name);
	$default_column = $this->fieldValidationRule->getColumn();
	$default_column = $form_state->getValue('column', $default_column);
	//if()
    $form['column'] = array(
      '#type' => 'select',
      '#title' => $this->t('Column of field'),
	  '#options' => $this->findColumn($default_field_name),
      '#default_value' => $default_column,
      '#required' => TRUE,
      '#prefix' => '<div id="edit-field-name-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE,	  
    );	
    $form['data'] = $this->fieldValidationRule->buildConfigurationForm(array(), $form_state);
    $form['error_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Error message'),
      '#default_value' => $this->fieldValidationRule->getErrorMessage(),
      '#required' => TRUE,
    );	
    $form['data']['#tree'] = TRUE;
	
	//drupal_set_message('term_id:' . var_export($form['data']));

    // Check the URL for a weight, then the fieldValidationRule, otherwise use default.
    $form['weight'] = array(
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->fieldValidationRule->getWeight(),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->fieldValidationRuleSet->urlInfo('edit-form'),
      '#attributes' => ['class' => ['button']],
    );
    return $form;
  }
  
  /**
   * Handles switching the configuration type selector.
   */
  public function updateColumn($form, FormStateInterface $form_state) {
    $form['column']['#default_value'] = '';
    $form['column']['#options'] = $this->findColumn($form_state->getValue('field_name'));
	
	//\Drupal::logger('field_validation')->notice('123:' . $form_state->getValue('field_name'));
	// \Drupal::logger('field_validation')->notice('123:' . var_export($form['column']['#options'], true));
	/*$form['column']['#options'] = array(
	  'value' => 'value',
	);
	*/
	// \Drupal::logger('field_validation')->notice('123abc:' . var_export($form['column']['#options'], true));
    return $form['column'];
	//return $form;
  }
  /**
   * Handles switching the configuration type selector.
   */
  protected function findColumn($field_name) {
    //\Drupal::logger('field_validation')->notice('1234:' . $field_name);
    $column_options = array(
      '' => $this->t('- Select -'),
    );
	if(empty($field_name)){
	  return $column_options;
	}
	$entity_type_id = $this->fieldValidationRuleSet->getAttachedEntityType();
	$field_info = \Drupal\field\Entity\FieldStorageConfig::loadByName($entity_type_id, $field_name);
	$schema = $field_info->getSchema();
	// \Drupal::logger('field_validation')->notice('1234:' . var_export($schema, true));
	if(!empty($schema['columns'])){
	  $columns = $schema['columns'];
	  foreach($columns as $key=>$value){
	    $column_options[$key] = $key;
	  }
	}
    return $column_options;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The fieldValidationRule configuration is stored in the 'data' key in the form,
    // pass that through for validation.
	$data = $form_state->getValue('data');
	if(empty($data)){
	  $data = array();
	}
      $field_validation_rule_data = (new FormState())->setValues($data);
      $this->fieldValidationRule->validateConfigurationForm($form, $field_validation_rule_data);
      // Update the original form values.
      $form_state->setValue('data', $field_validation_rule_data->getValues());

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The fieldValidationRule configuration is stored in the 'data' key in the form,
    // pass that through for submission.
    $field_validation_rule_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->fieldValidationRule->submitConfigurationForm($form, $field_validation_rule_data);
    // Update the original form values.
	//drupal_set_message('1234:' . $form_state->getValue('field_name'));
	//drupal_set_message('1234:' . $form_state->getValue('column'));
    $form_state->setValue('data', $field_validation_rule_data->getValues());
    $this->fieldValidationRule->setTitle($form_state->getValue('title'));
    $this->fieldValidationRule->setWeight($form_state->getValue('weight'));
	$this->fieldValidationRule->setFieldName($form_state->getValue('field_name'));
	$this->fieldValidationRule->setColumn($form_state->getValue('column'));
	$this->fieldValidationRule->setErrorMessage($form_state->getValue('error_message'));
    if (!$this->fieldValidationRule->getUuid()) {
	  //drupal_set_message('1234');
      $this->fieldValidationRuleSet->addFieldValidationRule($this->fieldValidationRule->getConfiguration());
      //drupal_set_message(var_export($this->fieldValidationRule->getConfiguration(), true));
	  //$test_rule = $this->fieldValidationRule;
	  //drupal_set_message(var_export($test_rule, true));
	}else{
	  //drupal_set_message(var_export($this->fieldValidationRule, true));
	  $this->fieldValidationRuleSet->deleteFieldValidationRule($this->fieldValidationRule);
	  $this->fieldValidationRuleSet->addFieldValidationRule($this->fieldValidationRule->getConfiguration());
    }
	$this->fieldValidationRuleSet->save();
    //drupal_set_message(var_export($this->fieldValidationRuleSet, true));
    drupal_set_message($this->t('The rule was successfully applied.'));
    $form_state->setRedirectUrl($this->fieldValidationRuleSet->urlInfo('edit-form'));
  }

  /**
   * Converts a field_validation_rule ID into an object.
   *
   * @param string $field_validation_rule
   *   The field_validation_rule ID.
   *
   * @return \Drupal\field_validation\FieldValidationRuleInterface
   *   The field_validation_rule object.
   */
  abstract protected function prepareFieldValidationRule($field_validation_rule);

}
