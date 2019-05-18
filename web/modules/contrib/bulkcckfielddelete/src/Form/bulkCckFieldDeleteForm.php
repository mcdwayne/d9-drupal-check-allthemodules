<?php

namespace Drupal\bulkcckfielddelete\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Bulk CCK Field Delete form.
 */
class BulkCckFieldDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cck_bulk_field_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['conten-types'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('Select the cck fields under the content types which needs to delete.'),
      '#title' => $this->t('Content types list'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    // Get fields of each content types.
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $entityManager = \Drupal::service('entity.manager');
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
      foreach (\Drupal::entityManager()->getFieldDefinitions('node', $contentType->id()) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && $field_name != 'promote') {
          $bundleFields[$contentType->id()][$field_name] = $field_definition->getLabel() . ' (' . $field_name . ')';
        }
      }
      $form['conten-types'][$contentType->id()] = [
        '#title' => $this->t($contentType->label()),
        '#type' => !empty($bundleFields[$contentType->id()]) ? 'checkboxes' : 'item',
        !empty($bundleFields[$contentType->id()]) ? '#options' : '#markup' => !empty($bundleFields[$contentType->id()]) ? $bundleFields[$contentType->id()] : 'No CCK fields exists.',
        '#value' => t('Delete'),
      ];
    }
    $form['conten-types']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $entityManager = \Drupal::service('entity.manager');
    foreach ($contentTypes as $contentType) {
      $contenttype_machine_name = $contentType->id();
      foreach ($form_state->getValue($contenttype_machine_name) as $key => $value) {
        if ($value === $key) {
          // Deleting field.
          FieldConfig::loadByName('node', $contenttype_machine_name, $key)->delete();
          $deleted_fields[$contenttype_machine_name][] = $value;
          // Store in log.
          \Drupal::logger('bulkCckFieldDeleteForm')->notice('@type: deleted fields %fields.', [
            '@type' => $contenttype_machine_name,
            '%fields' => $value,
          ]);
        }
      }
      if (count($deleted_fields[$contenttype_machine_name]) > 0) {
        drupal_set_message($this->t('Deleted field(s) <b>: @fields</b> from @type content type ', [
          '@fields' => implode(', ', $deleted_fields[$contenttype_machine_name]),
          '@type' => $contenttype_machine_name,
        ]), 'status');
      }
    }
  }

}
