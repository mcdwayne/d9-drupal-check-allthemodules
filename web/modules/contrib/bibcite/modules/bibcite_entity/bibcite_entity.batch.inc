<?php

/**
 * @file
 * Batch callbacks.
 */

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Batch operation. Change change source contributor id to target.
 *
 * @param int $source_id
 *   Source entity identifier.
 * @param int $target_id
 *   Target entity identifier.
 * @param string $entity_type_id
 *   Entity type identifier.
 * @param string $field_name
 *   Field name to search.
 * @param array $context
 *   Batch context array.
 */
function bibcite_entity_merge_entity($source_id, $target_id, $entity_type_id, $field_name, array &$context) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type = $entity_type_manager->getDefinition($entity_type_id);

  $reference_storage = $entity_type_manager->getStorage('bibcite_reference');
  $storage = $entity_type_manager->getStorage($entity_type_id);
  $source_entity = $storage->load($source_id);

  if (empty($context['results'])) {
    $context['results'] = [
      'references' => [],
      'references_count' => 0,
      'entities' => [],
      'entity_type_id' => $entity_type_id,
      'label' => $source_entity->label(),
    ];
  }

  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_id'] = 0;
    $context['sandbox']['max'] = $reference_storage->getQuery()->condition($field_name, $source_id)->count()->execute();

    $context['results']['references_count'] += $context['sandbox']['max'];
  }

  $query = $reference_storage->getQuery();
  $query
    ->condition($field_name, $source_id)
    ->condition('id', $context['sandbox']['current_id'], '>')
    ->sort('id')
    ->range(0, 10);

  $reference_entities = $reference_storage->loadMultiple($query->execute());

  /** @var \Drupal\bibcite_entity\Entity\ReferenceInterface $reference_entity */
  foreach ($reference_entities as $reference_entity) {
    $field = $reference_entity->get($field_name);
    $field_value = $field->getValue();
    _bibcite_entity_process_field_value($field_value, $source_id, $target_id);
    $field->setValue($field_value);

    $reference_entity->save();

    $context['results']['references'][] = $reference_entity->id() . ' : ' . $reference_entity->label();
    $context['sandbox']['progress']++;
    $context['sandbox']['current_id'] = $reference_entity->id();
  }

  $source_entity_message = t('Current @entity_type_label: @source_entity_label', [
    '@entity_type_label' => $entity_type->getLabel(),
    '@source_entity_label' => $source_entity->label(),
  ]);
  $context['message'] = $source_entity_message;

  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Find source field value and replace or remove it.
 *
 * @param array $field_value
 *   Field value array.
 * @param int $source_id
 *   Source entity identifier.
 * @param int $target_id
 *   Target entity identifier.
 */
function _bibcite_entity_process_field_value(array &$field_value, $source_id, $target_id) {
  $target_delta = _bibcite_entity_get_target_delta($field_value, $target_id);

  foreach ($field_value as $delta => $value) {
    if ($value['target_id'] == $source_id) {
      if (isset($target_delta)) {
        unset($field_value[$delta]);
      }
      else {
        $field_value[$delta]['target_id'] = $target_id;
      }
    }
  }
}

/**
 * Find target identifier in field value.
 *
 * @param array $field_value
 *   Field value array.
 * @param int $target_id
 *   Target entity identifier.
 *
 * @return int|null
 *   Position of target entity or NULL if target entity is not present in value.
 */
function _bibcite_entity_get_target_delta(array $field_value, $target_id) {
  foreach ($field_value as $delta => $value) {
    if ($value['target_id'] == $target_id) {
      return $delta;
    }
  }

  return NULL;
}

/**
 * Batch operation. Delete entity.
 *
 * @param int $source_id
 *   Source entity identifier to delete.
 * @param string $entity_type_id
 *   Entity type identifier.
 * @param string $field_name
 *   Field name to search.
 * @param array $context
 *   Batch context array.
 */
function bibcite_entity_merge_entity_delete($source_id, $entity_type_id, $field_name, array &$context) {
  $count = \Drupal::entityQuery('bibcite_reference')->condition($field_name, $source_id)->count()->execute();

  $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
  $entity = $storage->load($source_id);

  if ($count) {
    $context['results']['failed'][$entity->id()] = $entity->label();
  }
  else {
    $entity->delete();
    $context['results']['entities'][$entity->id()] = $entity->label();
  }
}

/**
 * Batch finished callback.
 *
 * @param bool $success
 *   A boolean indicating whether the batch has completed successfully.
 * @param array $results
 *   The value set in $context['results'] by operation callback.
 * @param array $operations
 *   If $success is FALSE, contains the operations that remained unprocessed.
 *
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 *   Redirect to collection route.
 */
function bibcite_entity_merge_entity_finished($success, array $results, array $operations) {
  $string_translation = \Drupal::translation();
  $entity_type_manager = \Drupal::entityTypeManager();
  $messenger = \Drupal::messenger();

  $reference_message = t('@count of @max references successfully processed', [
    '@count' => count($results['references']),
    '@max' => $results['references_count'],
  ]);
  $messenger->addStatus($reference_message);

  $entity_type = $entity_type_manager->getDefinition($results['entity_type_id']);
  $message = $string_translation->formatPlural(count($results['entities']),
    '%label has been successfully merged and deleted',
    '@count %plural_label have been successfully merged and deleted',
    [
      '%label' => $results['label'],
      '%plural_label' => $entity_type->getPluralLabel(),
    ]);
  $messenger->addStatus($message);

  if (!empty($results['failed'])) {
    $messenger->addError(t("These @entity_type processed with errors and have not been deleted:\n", [
      '@entity_type' => $entity_type->getPluralLabel(),
    ]));
    foreach ($results['failed'] as $id => $label) {
      $messenger->addError("$label ($id)\n");
    }
  }

  $redirect_url = new Url("entity.{$results['entity_type_id']}.collection");
  return new RedirectResponse($redirect_url->toString());
}
