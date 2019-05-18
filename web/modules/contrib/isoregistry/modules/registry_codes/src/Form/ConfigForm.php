<?php

namespace Drupal\registry_codes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @author Balschmiter
 * 
 */


class ConfigForm extends ConfigFormBase {
    /** @var string Config settings */
  const SETTINGS = 'registry_codes.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'registry_codes_admin_settings';
  }
  
  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $field_types = array('field_codes_code','field_codes_definition','field_namespace','field_codes_parent');

    $config = $this->config(static::SETTINGS);
    $form['codeurl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' =>$this->t('leave empty for using the base url'),
      '#default_value' => $config->get('codeurl'),
    );  
    
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    $defaultValues = array();
    
    foreach($types as $type) {
      $entityManager = \Drupal::service('entity_field.manager');
      $fields = $entityManager->getFieldDefinitions('node', $type->id());
      $fieldNames = array();
      foreach ($fields as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle())) {
          array_push($fieldNames,$field_name);
        }
      }
      //Check if all needed fields are contained in the content type
      if( count(array_diff($field_types,$fieldNames))  < 1){
        $content_types[$type->id()] = $type->label();
      }
      
    }
    
    
    foreach($config->get('enabled_content_types') as $ct) {
      if($ct != '0'){
        array_push($defaultValues, $ct);
      }
    };
    
    $form['enabled_content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Enabled Content Types',
      '#description' => 'Run preprocess for all enabled content types only',
      '#options' => $content_types,
      '#default_value' => $defaultValues,
    );

    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
     $this->configFactory->getEditable(static::SETTINGS)
    // Set the submitted configuration setting
    ->set('codeurl', $form_state->getValue('codeurl'))
    ->set('enabled_content_types', $form_state->getValue('enabled_content_types'))
    ->save();

    parent::submitForm($form, $form_state);
  }
}