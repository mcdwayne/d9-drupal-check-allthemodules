<?php

namespace Drupal\bulk_copy_fields;

/**
 * BulkCopyFields.
 */
class BulkCopyFields {

  /**
   * {@inheritdoc}
   */
  public static function copyFields($entities, $fields, $languages, &$context) {
    $message = 'Copying Fields...';
    $results_entities = [];
    $results_fields = [];
    $copy = FALSE;
    foreach ($entities as $entity) {
      foreach ($languages as $langcode) {
        if (in_array($langcode, array_keys($entity->getTranslationLanguages()))) {
          $entity = $entity->getTranslation($langcode);
          foreach ($fields as $field_from => $field_to) {
            if ($entity->hasField($field_from) && $entity->hasField($field_to)) {
              $values = $entity->get($field_from)->getValue();
              $field_def_to = $entity->get($field_to)->getFieldDefinition()->getFieldStorageDefinition();
              $field_def_from = $entity->get($field_from)->getFieldDefinition()->getFieldStorageDefinition();

              // Check for entity reference and entity reference revisions.
              if ((strpos($field_def_to->getType(), 'entity_reference') !== FALSE) || (strpos($field_def_from->getType(), 'entity_reference') !== FALSE)) {
                if (($target_type_to = $field_def_to->getSetting('target_type')) != ($target_type_from = $field_def_from->getSetting('target_type'))) {
                  drupal_set_message(t("The from field @field_from has target type @target_type_from and does not match the to field @field_to target type @target_type_to",
                    [
                      '@field_from' => $field_from,
                      '@target_type_from' => $target_type_from,
                      '@field_to' => $field_to,
                      '@target_type_to' => $target_type_to,
                    ]), 'error');
                  continue;
                }
                // Check for entity reference to entity reference revisions.
                if ($field_def_to->getType() == 'entity_reference_revisions' && $field_def_from->getType() == 'entity_reference') {
                  foreach ($values as $item_num => $value) {
                    $storage = \Drupal::entityTypeManager()->getStorage($target_type_to);
                    $values[$item_num]['target_revision_id'] = $storage->load($values[$item_num]['target_id'])->getRevisionId();
                  }
                }
              }
              $entity->get($field_to)->setValue($values);
              $copy = TRUE;
              if (!in_array($field_to, $results_fields)) {
                $results_fields[] = $field_to;
              }
            }
          }
          if ($copy) {
            $entity->setNewRevision();
            $entity->save();
            $results_entities[] = $entity->id();
          }
        }
        else {
          continue;
        }
      }
    }
    $context['message'] = $message;
    $context['results']['results_entities'] = $results_entities;
    $context['results']['results_fields'] = $results_fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bulkCopyFieldsFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message_field = \Drupal::translation()->formatPlural(
        count($results['results_fields']),
        'One field processed', '@count fields processed'
      );
      $message_entity = \Drupal::translation()->formatPlural(
        count($results['results_entities']),
        'One entity', '@count entities'
      );
      $message = $message_field.' on '.$message_entity;
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
