<?php
namespace Drupal\content_type_clone\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;

/**
 * Class CloneContentTypeForm.
 *
 * @package Drupal\content_type_clone\Form
 */
class CloneContentTypeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clone_content_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {  
    //Get the requested node type from url parameter.
    $nodeTypeName = \Drupal::request()->query->get('node_type');

    //Load the node type.
    $entity = NodeType::load($nodeTypeName);

    //Source content type fieldset.
    $form['source'] = array(
      '#type' => 'details',
      '#title' => t('Content type source'),
      '#open' => FALSE, 
    );

    //Source content type name.
    $form['source']['source_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
      '#attributes' => array('readonly' => 'readonly'),
    );

    //Source content type machine name.
    $form['source']['source_machine_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Machine name'),
      '#required' => TRUE,
      '#default_value' => $entity->id(),
      '#attributes' => array('readonly' => 'readonly'),
    );

    //Source content type description.
    $form['source']['source_description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#required' => FALSE,
      '#default_value' => $entity->getDescription(),
      '#attributes' => array('readonly' => 'readonly'),
    );

    //Target content type fieldset.
    $form['target'] = array(
      '#type' => 'details',
      '#title' => t('Content type target'),
      '#open' => TRUE, 
    );

    //Target content type name.
    $form['target']['target_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#required' => TRUE,
    );

    //Target content type machine name.
    $form['target']['target_machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Machine name'),
      '#required' => TRUE,
      '#description' => $this->t('A unique name for this item. It must only contain lowercase letters, numbers, and underscores.'),
    );

    //Target content type description
    $form['target']['target_description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#required' => FALSE,
    );

    //Copy nodes checkbox.
    $form['copy_source_nodes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Copy all nodes from the source content type to the target content type'),
      '#required' => FALSE,
    );

    //Delete nodes checkbox.
    $form['delete_source_nodes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delete all nodes from the source content type after they have been copied to the target content type'),
      '#required' => FALSE,
    );

    //Token pattern fieldset.
    $form['patterns'] = array(
      '#type' => 'details',
      '#title' => t('Replacement patterns'),
      '#open' => FALSE, 
    );

    //Display token options.
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      // Display the node title pattern field.
      $placeholder = t('Clone of @title', array('@title' => '[node:title]'));
      $form['patterns']['title_pattern'] = array(
        '#type' => 'textfield',
        '#title' => t('Node title pattern'),
        '#attributes' => array (
          'placeholder' => $placeholder
        )
      );

      $form['patterns']['token_tree'] = array(
        '#title' => t('Tokens'),
        '#theme' => 'token_tree_link',
        '#token_types' => array('node'),
        '#show_restricted' => TRUE,
        '#global_types' => TRUE,
        '#required' => TRUE,
      );
    }
    else {
      $form['patterns']['token_tree'] = array(
        '#markup' => '<p>' . t('Enable the <a href="@drupal-token">Token module</a> to view the available token browser.',
          array(
            '@drupal-token' => 'http://drupal.org/project/token',
          )
        ) . '</p>',
      );
    }

    //Clone submit button.
    $form['cct_clone'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Clone content type'),
    );

    //Return the result.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //Get the submitted form values.
    $values = $form_state->getValues();

    //Retrieve the existing content type names.
    $contentTypesNames = $this->getContentTypesList();

    //Check if the machine name already exists.
    if (in_array($values['target_machine_name'], $contentTypesNames)) {
      $form_state->setErrorByName(
        'target_machine_name', 
        $this->t('The machine name of the target content type already exists.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Create the batch process.
    $batch = array(
      'title' => t('Batch operations'),
      'operations' => $this->buildOperationsList($form_state),
      'finished' => '\Drupal\content_type_clone\Form\CloneContentType::cloneContentTypeFinishedCallback',
      'init_message' => t('Performing batch operations...'),
      'error_message' => t('Something went wrong. Please check the errors log.'),
    );

    //Set the batch.
    batch_set($batch);
  }

  /**
   * Builds the operations array for the batch process.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   * @return array
   *   An array of operations to perform
   */
  protected function buildOperationsList(FormStateInterface $form_state) {
    //Get the form values.
    $values = $form_state->getValues();
 
    //Prepare the operations array.
    $operations = array();

    //Clone content type operation.
    $operations[] = [
      '\Drupal\content_type_clone\Form\CloneContentType::cloneContentType',
      [$values],
    ];

    //Clone fields operations.
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $values['source_machine_name']);
    foreach ($fields as $field) {
      if (!empty($field->getTargetBundle())) {
        $data = ['field' => $field, 'values' => $values];
        $operations[] = [
          '\Drupal\content_type_clone\Form\CloneContentType::cloneContentTypeField',
          [$data],
        ];
      }
    }

    //Clone nodes operations.
    if ((int)$values['copy_source_nodes'] == 1) {
      $nids = \Drupal::entityQuery('node')->condition('type', $values['source_machine_name'])->execute();
      foreach ($nids as $nid) {
        if ((int) $nid > 0) {
          $operations[] = [
            '\Drupal\content_type_clone\Form\CloneContentType::copyContentTypeNode',
            [$nid, $values],
          ];
        }
      }
    }

    //Return the result.
    return $operations;
  }

  protected function getContentTypesList() {
    // Get the existing content types.
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    //Retrieve the existing content type names.
    $contentTypesNames = [];
    foreach ($contentTypes as $contentType) {
        $contentTypesNames[] = $contentType->id();
    }

    //Return the result.
    return $contentTypesNames;
  }
}