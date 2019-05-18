<?php
/**
 * @file
 * Contains \Drupal\data_import\Form\importerMappingForm.
 */
 
namespace Drupal\data_import\Form;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\data_import\Controller;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\field\FieldConfigInterface;
use Drupal\taxonomy\Entity\Vocabulary;

class importerMappingForm extends FormBase {

  
  public function getFormId() {
    return 'importer_mapping_form';
  }
  
  public function contentTypeFields($entity_type_id, $bundle) {
    $entityManager = \Drupal::service('entity.manager');
    $fields = [];

    if (!empty($entity_type_id) && !empty($bundle) ) {
      $fields = array_filter(
          $entityManager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
        return $field_definition instanceof FieldConfigInterface;
      }
      );
    }

    return $fields;
  }

  public function buildForm(array $form, FormStateInterface $form_state, $importer_id = '') {

    // Init all dynamic value
    if (empty($form_state->get('#mappings'))) {

      // Importer
      $form_state->set('#importer', data_importer_load($importer_id));

      // Mappings
      $form_state->set('#origin_mappings', load_data_importer_mappings($importer_id));
      $form_state->set('#mappings', $form_state->get('#origin_mappings'));
      $form_state->set('#key_mappings', array_keys($form_state->get('#mappings')));

      // Content Type
      $values_array = array();
      foreach (node_type_get_types() as $key => $type) {
        $values_array[$key] = $type->get('name');
      }
      $form_state->set('#node', $values_array);

      //Taxonomy
      $values_array = array();
      foreach (Vocabulary::loadMultiple() as $key => $type) {
        $values_array[$key] = $type->label();
      }
      $form_state->set('#taxonomy_term', $values_array);
      
      // Init entity & bundle_name default value
      $entity = ($form_state->get('#importer') && $form_state->get('#importer')['entity']) ? $form_state->get('#importer')['entity'] : 'node';
      $bundle_name = ($form_state->get('#importer') && $form_state->get('#importer')['bundle_name']) ? $form_state->get('#importer')['bundle_name'] : array_keys($form_state->get('#'.$entity))[0];

    }

    // Update entity & Bundle name
    $entity = !empty($form_state->getValue('entity')) ? $form_state->getValue('entity') : $entity;
    $bundle_name = !empty($form_state->getValue('bundle_name')) ? $form_state->getValue('bundle_name') : $bundle_name;

    //Update content_type
    $bundle_name = $entity == 'user' ? 'user' : $bundle_name;
    $entity_fields = self::contentTypeFields($entity, $bundle_name);
    $fields = array();
    foreach($entity_fields as $machine_name => $field){
      $fields[$machine_name] = $machine_name;
    }

    // Update label & Base fields
    $label = '';
    switch ($entity) {
      case 'node':
        $label = t('Content Type');
        $fields = array('status' => 'status') + $fields;
        $fields = array('title' => 'title') + $fields;
        break;

      case 'taxonomy_term':
        $label = t('Vocabulary');
        $fields = array('status' => 'status') + $fields;
        $fields = array('name' => 'name') + $fields;
        break;

      case 'user':
        $fields = array('roles' => 'roles') + $fields;
        $fields = array('status' => 'status') + $fields;
        $fields = array('password' => 'password') + $fields;
        $fields = array('mail' => 'mail') + $fields;
        $fields = array('name' => 'name') + $fields;
        break;

      default:
        $label = t('Bundle Name');
        break;
    }


    $form['container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="mapping-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 0,
    );
  
    $form['container']['entity'] = array(
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#options' => array(
        'node' => t('Node'),
        'user' => t('User'),
        'taxonomy_term' => t('Taxonomy')
      ),
      '#default_value' => $entity,
      '#description' => count($form_state->get('#mappings')) ? t('Remove all rows to edit this.') : t('Please select the Entity type.'),
      '#disabled' => count($form_state->get('#mappings')) ? TRUE : FALSE,
      '#ajax' => array(
        'callback' => '::ajax_update_mapping_container',
        'wrapper' => 'mapping-wrapper',
      ),
      '#weight' => 0,
    );

  // Init ELemet Type
    if ($entity != 'user') {
      $form['container']['bundle_name'] = array(
        '#type' => 'select',
        '#title' => $label,
        '#options' => $form_state->get('#'.$entity),
        '#default_value' => $bundle_name,
        '#description' => count($form_state->get('#mappings')) ? t('Remove all rows to edit this.') : t("Please select the @name", array('@name' => $label)),
        '#disabled' => count($form_state->get('#mappings')) ? TRUE : FALSE,
        '#ajax' => array(
          'callback' => '::ajax_update_mapping_table',
          'wrapper' => 'mappings-table',
        ),
        '#weight' => 0,
      );
    }

    $form['container']['table'] = array(
      '#weight' => 1,
      '#prefix' => '<table id="mappings-table"><tr><th>Source</th><th>Field Name</th><th>Override</th><th>Primary Key</th><th> </th></tr>',
      '#suffix' => '</table>',
      '#description' => 'Source column represents the index of the data you want to import. e.g: enter "0" to represent column 1, "1" to represent column 2, ...'
    );
  
    $form['container']['help_text'] = array(
      '#markup' => '
      <br/>
      <small>
        <i>* Source column represents the index of the data you want to import. e.g: 
        enter "0" to represent column 1, "1" to represent column 2, ...</i>
        <br/>
        <i>* Check Override to replace content data with import data on next run else import data will be 
        used on content creation only.</i>
        <br/>
        <i>* Primary key is used to identify if content is to be updated or created.</i>
      </small>
      <br/><br/>',
      '#weight' => 2
    );
  
    $form['container']['table']['rows']['#tree'] = TRUE;
  
    $primary_key_disabled = FALSE;
    foreach ($form_state->get('#mappings') as $mapping) {
      if ($mapping['primary_key'] > 0) {
        $primary_key_disabled = TRUE;
      }
    }
  
    foreach ($form_state->get('#mappings') as $id => $mapping) {

      $mapping += array(
        'importer_id' => $importer_id,
        'source' => '',
        'field_name' => '',
        'override' => 0,
        'primary_key' => 0
      );
  
      $form['container']['table']['rows'][$id] = array(
        '#tree' => TRUE,
        '#prefix' => '<tr>',
        '#suffix' => '</tr>',
      );
  
      $form['container']['table']['rows'][$id]['importer_id'] = array(
        '#type' => 'hidden',
        '#value' => $mapping['importer_id'],
      );
  
      $form['container']['table']['rows'][$id]['source'] = array(
        '#type' => 'textfield',
        '#default_value' => $mapping['source'],
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );

      $form['container']['table']['rows'][$id]['field_name'] = array(
        '#type' => 'select',
        '#options' => $fields,
        '#default_value' => $mapping['field_name'],
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );
  
      $form['container']['table']['rows'][$id]['override'] = array(
        '#type' => 'checkbox',
        '#default_value' => $mapping['override'],
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );
  
      $form['container']['table']['rows'][$id]['primary_key'] = array(
        '#type' => 'radio',
        '#name' => 'primary_key',
        '#default_value' => $mapping['primary_key'] ? $id : 0,
        '#return_value' => $id,
        '#disabled' => $primary_key_disabled,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      );
  
      $form['container']['table']['rows'][$id]['remove'] = array(
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#button-id' => $id,
        '#name' => 'remove-' . $id,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
        '#submit' => array('::remove_mapping_row'),
        '#ajax' => array(
          'callback' => '::ajax_update_mapping_container',
          'wrapper' => 'mapping-wrapper',
        ),
      );
    }
  
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 1,
    );
  
    $form['add_new'] = array(
      '#type' => 'submit',
      '#value' => t('Add new row'),
      '#submit' => array('::add_new_mapping_row'),
      '#ajax' => array(
        'callback' => '::ajax_update_mapping_container',
        'wrapper' => 'mapping-wrapper',
      ),
      '#weight' => 3,
    );  

    return $form;
  }

  /**
   * Update container form
   */
  public function ajax_update_mapping_container(array &$form, FormStateInterface &$form_state) {
    return $form['container'];
  }

  /**
   * Update Tbale form
   */
  public function ajax_update_mapping_table(array &$form, FormStateInterface &$form_state) {
    return $form['container']['table'];
  }

  /**
   * Submit handler for the "add-new-row" button.
   * Increments the max counter and causes a rebuild.
   */
  public function add_new_mapping_row(array &$form, FormStateInterface $form_state) {
    // New item id
    $rows = $form_state->get('#mappings');
    end($rows);
    $new_id = $rows ? key($rows) + 1 : 1;
    $rows[$new_id] = array();
    $form_state->set('#mappings', $rows);
    $form_state->setRebuild(TRUE);
    // return $form['container'];
  }

  /**
   * Callback for Remove mapping table row.
   */
  public function remove_mapping_row(array &$form, FormStateInterface $form_state) {
    $triggerElement = $form_state->getTriggeringElement();
    $id = $triggerElement['#button-id'];
    $rows = $form_state->get('#mappings');
    unset($rows[$id]);
    $form_state->set('#mappings', $rows);
    $form_state->setRebuild(TRUE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Updte & Reset Mapping
    if(!empty($form_state->getValue('rows'))) {
      foreach ($form_state->getValue('rows') as $id => $mapping) {
        //Primary key
        if(isset($_POST['primary_key'])) $mapping['primary_key'] = ($id == $_POST['primary_key']) ? 1 : 0;

        if (in_array($id, $form_state->get('#key_mappings'))) {
          $mapping['id'] = $id;
          $key = array_search($id, $form_state->get('#key_mappings'));
          unset($form_state->get('#key_mappings')[$key]);

          // Update Mapping
          save_data_import_mappings($mapping, 'update');
        } else {

          // Insert New Mapping
          save_data_import_mappings($mapping, 'insert');
        }
      }
    }

    // Remove Mapping
    foreach ($form_state->get('#key_mappings') as $key) {
      $mapping['id'] = $key;
      save_data_import_mappings($mapping, 'delete');
    }

    // Update Entity Type
    $entity_type = $form_state->get('#importer');
    $entity_type['entity'] = $form_state->getValue('entity');
    $entity_type['bundle_name'] = !empty($form_state->getValue('bundle_name')) ? $form_state->getValue('bundle_name') : '';
    $entity_type['bundle_name'] = $form_state->getValue('entity') == 'user' ? 'user' : $entity_type['bundle_name'];
    $form_state->set('#importer', $entity_type);
    data_importer_save($form_state->get('#importer'));

    drupal_set_message(t('The configuration have been saved.'));
  }

}