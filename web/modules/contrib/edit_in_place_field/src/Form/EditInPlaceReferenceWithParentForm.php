<?php

namespace Drupal\edit_in_place_field\Form;

use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\edit_in_place_field\Ajax\RebindJSCommand;

/**
 * Class EditInPlaceReferenceWithParentForm.
 *
 * @package Drupal\edit_in_place_field\Form
 */
class EditInPlaceReferenceWithParentForm extends EditInPlaceFieldReferenceForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edit_in_place_reference_with_parent_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data = []) {
    $form = parent::buildForm($form, $form_state, $data);
    $form['parent_ids'] = [
      '#type' => 'hidden',
      '#value' => implode(', ', array_keys($data['choice_lists'])),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getInPlaceField ($data) {
    $choice_fields = [];
    foreach($data['choice_lists'] as $parent_id => $choices) {
      $choice_fields['in_place_field'.$parent_id] = [
        '#title' => isset($data['parent_labels'][$parent_id])?$data['parent_labels'][$parent_id]:'',
        '#type' => 'edit_in_place_field_select',
        '#options' => $choices,
        '#value' => isset($data['selected'][$parent_id])?$data['selected'][$parent_id]['ids']:[],
        '#name' => 'in_place_field'.$parent_id.'[]',
        '#attributes' => [
          'multiple' => ($data['cardinality'] !== 1)?TRUE:FALSE,
        ],
      ];
    }
    return $choice_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function processRequest() {
    // Get data from ajax request.
    $field_name = \Drupal::requestStack()->getCurrentRequest()->get('field_name');
    $entity_type = \Drupal::requestStack()->getCurrentRequest()->get('entity_type');
    $entity_id = \Drupal::requestStack()->getCurrentRequest()->get('entity_id');
    $ajax_replace = \Drupal::requestStack()->getCurrentRequest()->get('ajax_replace');
    $parent_ids = \Drupal::requestStack()->getCurrentRequest()->get('parent_ids');
    $label_substitution = \Drupal::requestStack()->getCurrentRequest()->get('label_substitution');
    $parent_ids = explode(', ', $parent_ids);

    // Retrieve the selected values.
    $field_values = [];
    foreach($parent_ids as $parent_id) {
      $field_values = array_merge($field_values, \Drupal::requestStack()->getCurrentRequest()->get('in_place_field'.$parent_id, []));
    }
    // Get the current langcode.
    $replace_data = explode('-', $ajax_replace);
    $entity_langcode = end($replace_data);

    return [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'entity_id' => $entity_id,
      'ajax_replace' => $ajax_replace,
      'field_values' => $field_values,
      'entity_langcode' => $entity_langcode,
      'label_substitution' => $label_substitution,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function processResponse($data) {
    $field_name = $data['field_name'];
    $entity_type = $data['entity_type'];
    $entity_id = $data['entity_id'];
    $ajax_replace = $data['ajax_replace'];
    $field_values = $data['field_values'];
    $entity_langcode = $data['entity_langcode'];
    $label_substitution = $data['label_substitution'];

    $selected_entities = [];
    if (empty($field_name) || empty($entity_type) || empty($entity_id)) {
      return $this->getResponse(parent::ERROR_INVALID_DATA, [
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
      ]);
    }

    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    try {
      $entity = $entity->getTranslation($entity_langcode);
    }catch(\Exception $e){}

    if (empty($entity)) {
      return $this->getResponse(parent::ERROR_ENTITY_CANNOT_BE_LOADED, [
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
      ]);
    }

    try {
      // Save field data.
      $entity->{$field_name} = $field_values;
      $entity->save();

      // Retrieve data to be pass to the template.
      foreach($entity->{$field_name} as $field_data) {
        $child_entity = $field_data->entity;
        try {
          $child_entity = $child_entity->getTranslation($entity_langcode);
        }catch(\Exception $e){}
        $parent_id = $child_entity->get('parent')->target_id;
        $selected_entities[$parent_id]['ids'][] = $child_entity->id();
        $entity_label = $child_entity->label();
        if (!empty($label_substitution) && isset($child_entity->{$label_substitution}) && !empty($child_entity->{$label_substitution}->value)) {
          $entity_label = $child_entity->{$label_substitution}->value;
        }
        $selected_entities[$parent_id]['labels'][] = $entity_label;
        $selected_entities[$parent_id]['entities'][] = $child_entity;
      }
    }
    catch(EntityStorageException $e) {
      return $this->getResponse(parent::ERROR_DATA_CANNOT_BE_SAVED, ['error' => $e->getMessage()]);
    }

    // Render entities labels.
    $labels_html = \Drupal::theme()->render('edit_in_place_reference_with_parent_label', [
      'entities' => $selected_entities,
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'entity_id' => $entity_id,
      'lang_code' => $entity_langcode,
    ]);

    // Prepare response.
    $response = $this->getResponse();

    // Labels replacement.
    $response->addCommand(new InsertCommand('.'.$ajax_replace.' .fieldset-wrapper .entity-label', $labels_html));

    // Bind JavaScript events after html replacement from ajax call.
    $response->addCommand(new RebindJSCommand('rebindJS', '.'.$ajax_replace));
    return $response;
  }

}
