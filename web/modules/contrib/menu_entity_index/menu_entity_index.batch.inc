<?php

/**
 * @file
 * Functions for batch processing in Menu Entity Index module.
 */

use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Batch callback to scan menus of a given type for referenced entities.
 *
 * As this is used in a static context, we need to get all services by hand.
 *
 * @param array $menus
 *   Menu names to scan during the batch process.
 * @param array|\ArrayAccess $context
 *   The batch context array, passed by reference. This contains the following
 *   properties:
 *   - 'finished': A float number between 0 and 1 informing the processing
 *     engine of the completion level for the operation. 1 (or no value
 *     explicitly set) means the operation is finished: the operation will not
 *     be called again, and execution passes to the next operation or the
 *     callback_batch_finished() implementation. Any other value causes this
 *     operation to be called again; however it should be noted that the value
 *     set here does not persist between executions of this callback: each time
 *     it is set to 1 by default by the batch system.
 *   - 'sandbox': This may be used by operations to persist data between
 *     successive calls to the current operation. Any values set in
 *     $context['sandbox'] will be there the next time this function is called
 *     for the current operation. For example, an operation may wish to store a
 *     pointer in a file or an offset for a large query. The 'sandbox' array key
 *     is not initially set when this callback is first called, which makes it
 *     useful for determining whether it is the first call of the callback or
 *     not.
 *
 * @code
 *   if (empty($context['sandbox'])) {
 *     // Perform set-up steps here.
 *   }
 * @endcode
 *
 *     The values in the sandbox are stored and updated in the database between
 *     http requests until the batch finishes processing. This avoids problems
 *     if the user navigates away from the page before the batch finishes.
 *   - 'message': A text message displayed in the progress page.
 *   - 'results': The array of results gathered so far by the batch processing.
 *     This array is highly useful for passing data between operations. After
 *     all operations have finished, this is passed to callback_batch_finished()
 *     where results may be referenced to display information to the end-user,
 *     such as how many total items were processed.
 *   It is discouraged to typehint this parameter as an array, to allow an
 *   object implement \ArrayAccess to be passed.
 */
function menu_entity_index_track_batch(array $menus, &$context) {
  $tracker = \Drupal::service('menu_entity_index.tracker');
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $translation = \Drupal::service('string_translation');
  $entity_query = \Drupal::service('entity.query');
  $batch_size = 10;

  $query = $entity_query->get('menu_link_content', 'OR');
  foreach ($menus as $menu) {
    $query->condition('menu_name', $menu);
  }

  if (empty($context['sandbox'])) {
    $count_query = clone $query;

    $context['sandbox']['max'] = $count_query->count()->execute();
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['offset'] = 0;
    $context['message'] = $translation->formatPlural($context['sandbox']['max'], 'Scanning 1 menu link for referenced entities.', 'Scanning @count menu links for referenced entities.');
  }

  $entity_ids = $query->range($context['sandbox']['offset'], $batch_size)->execute();

  $storage = $entity_type_manager->getStorage('menu_link_content');
  foreach ($storage->loadMultiple($entity_ids) as $entity_id => $entity) {
    $context['results'][] = $translation->translate('Scanning menu link with id @entity_id.', [
      '@entity_id' => $entity->id(),
    ]);

    if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {
      // Scan all languages of the entity.
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $tracker->updateEntity($entity->getTranslation($langcode));
      }
    }
    else {
      $tracker->updateEntity($entity);
    }
  }

  $context['sandbox']['progress'] += count($entity_ids);
  $context['sandbox']['offset'] = $context['sandbox']['offset'] + $batch_size;
  if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  else {
    $context['message'] = $translation->translate('Completed scanning of menu links.');
  }
}
