<?php

/**
 * @file
 * Contains \Drupal\content_entity_builder\Form\ContentEntityBuilderExportForm.
 */

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\content_entity_builder\Export\ContentEntityBuilderExportHelper;
/**
 * pdf generate form for chengji.
 */
class ContentEntityBuilderExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_entity_builder_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Module name'),
      '#required' => TRUE,
    );
    $form['name'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => '\Drupal\content_entity_builder\Form\ContentEntityBuilderExportForm::moduleExists',
      ),
      '#required' => TRUE,
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Module description'),
      '#required' => TRUE,
    );

    $content_types = \Drupal::entityTypeManager()->getStorage('content_type')->loadMultiple();

    $entity_type_options = [];
	foreach($content_types as $content_type){
      $entity_type_options[$content_type->id()] = $content_type->label();
	}
    $form['content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Choose the content entity type that you want to export'),
	  '#options' => $entity_type_options,
      '#required' => TRUE,	  
    );
		
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      // Prevent op from showing up in the query string.
      '#name' => '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
	$content_types = array_filter($form_state->getValue('content_types'));

	$config = [
	  'label' => $form_state->getValue('label'),
	  'name' => $name,
	  'description' => $form_state->getValue('description'),
	  'content_types' => $content_types,
	];
	
	$export_helper = new ContentEntityBuilderExportHelper($config);
	$export_helper->generateArchiveTarFile();

	$form_state->setRedirect('content_entity_builder.download', array('name' =>$name));	
  }
  
  /**
   * Helper function for exists check.
   */  
  public static function moduleExists($module) {
    return \Drupal::moduleHandler()->moduleExists($module);  
  }
    
  

  
}
