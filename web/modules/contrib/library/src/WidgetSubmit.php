<?php

namespace Drupal\library;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\library\Entity\LibraryItem;
use Drupal\library\Plugin\Field\FieldWidget\LibraryItemFieldWidget;

/**
 * Performs widget submission.
 *
 * Widgets don't save changed entities, nor do they delete removed entities.
 * Instead, they flag them so that changes are only applied when the main form
 * is submitted.
 */
class WidgetSubmit {

  /**
   * Attaches the widget submit functionality to the given form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function attach(array &$form, FormStateInterface $form_state) {
    foreach ($form['actions'] as $key => $action) {
      if (isset($form['actions'][$key]['#submit'])) {
        $form['actions'][$key]['#submit'][] = [get_called_class(), 'doSubmit'];
      }
    }
  }

  /**
   * Submits the widget elements, saving and deleted entities where needed.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function doSubmit(array $form, FormStateInterface $form_state) {
    $callback = $form_state->getFormObject();
    $entity = $callback->getEntity();
    /** @var \Drupal\node\Entity\Node $entity */
    $fields = $entity->getFieldDefinitions();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
    $libraryFields = [];
    foreach ($fields as $field) {
      if ($field->getType() == 'library_item_field_type') {
        $libraryFields[] = $field;
      }
    }

    if (count($libraryFields) > 1) {
      drupal_set_message(t('Only one library field per bundle supported.'), 'error');
      return;
    }

    if (isset($libraryFields[0])) {
      $submittedValues = $form_state->getValue($libraryFields[0]->getName());
      self::processLibraryItems($libraryFields[0], $submittedValues, $entity);
    }

  }

  /**
   * Process library items.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Relevant field.
   * @param array $submittedValues
   *   Submitted values.
   * @param \Drupal\Core\Entity\EntityInterface $parentEntity
   *   Parent entity.
   */
  private static function processLibraryItems(FieldDefinitionInterface $field, array $submittedValues, EntityInterface $parentEntity) {
    $fieldName = $field->getName();
    $entriesChanged = FALSE;
    $savedReferences = $parentEntity->get($fieldName)->getValue();

    foreach ($submittedValues as $delta => $value) {
      if (isset($value['library']['item_id'])) {
        $itemEntity = LibraryItem::load($value['library']['item_id']);
      }
      else {
        $itemEntity = LibraryItem::create();
      }
      $b = $itemEntity->get('barcode')->value;
      if ($field->getSetting('barcode_generation') && empty($itemEntity->get('barcode')->value)) {
        $itemEntity->set('barcode', LibraryItemFieldWidget::findHighestBarcode() + 1);
      }
      else {
        $itemEntity->set('barcode', $value['library']['barcode']);
      }

      if (!isset($value['library']['nid'])) {
        $itemEntity->set('nid', ['target_id' => $parentEntity->id()]);
      }

      $itemEntity->set('library_status', $value['library']['library_status']);
      $itemEntity->set('notes', $value['library']['notes']);
      $itemEntity->set('in_circulation', $value['library']['in_circulation']);
      foreach ($savedReferences as $key => $reference) {
        if (isset($value['library']['item_id']) && $reference['target_id'] == $value['library']['item_id'] && $value['remove'] == 1) {
          unset($savedReferences[$key]);
          $entriesChanged = TRUE;
        }
      }
      if ($value['remove'] == 1) {
        $itemEntity->delete();
      }
      else {
        $itemEntity->save();
      }
      if (!isset($value['library']['item_id'])) {
        $savedReferences[] = ['target_id' => $itemEntity->id()];
        $entriesChanged = TRUE;
      }
    }
    if ($entriesChanged == TRUE) {
      $parentEntity->set($fieldName, $savedReferences);
      $parentEntity->save();
    }
  }

}
