<?php

/**
 * @file
 * Post-update hooks of Menu Entity Index module.
 */

use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Reinitializes menu entity index.
 */
function menu_entity_index_post_update_reindex_8x1_1(&$sandbox) {
  $tracker = \Drupal::service('menu_entity_index.tracker');

  if (!isset($sandbox['menus'])) {
    $sandbox['menus'] = $tracker->getTrackedMenus();
  }

  if (empty($sandbox['menus'])) {
    return;
  }

  $entity_type_manager = \Drupal::service('entity_type.manager');
  $entity_query = \Drupal::service('entity.query');
  $batch_size = 10;

  $query = $entity_query->get('menu_link_content', 'OR');
  foreach ($sandbox['menus'] as $menu) {
    $query->condition('menu_name', $menu);
  }

  if (!isset($sandbox['max'])) {
    $database = \Drupal::service('database');
    $database->delete('menu_entity_index')
      ->condition('menu_name', (array) $sandbox['menus'], 'IN')
      ->execute();

    $count_query = clone $query;

    $sandbox['max'] = $count_query->count()->execute();
    $sandbox['progress'] = 0;
    $sandbox['offset'] = 0;
  }

  $entity_ids = $query->range($sandbox['offset'], $batch_size)->execute();

  $storage = $entity_type_manager->getStorage('menu_link_content');
  foreach ($storage->loadMultiple($entity_ids) as $entity_id => $entity) {
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

  $sandbox['progress'] += count($entity_ids);
  $sandbox['offset'] = $sandbox['offset'] + $batch_size;
  if ($sandbox['progress'] < $sandbox['max']) {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
  }
  else {
    $translation = \Drupal::service('string_translation');
    return $translation->translate('Completed scanning of menu links.');
  }
}
