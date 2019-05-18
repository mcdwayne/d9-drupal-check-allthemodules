<?php

/**
 * @file
 * Contains entity_collection.page.inc.
 *
 * Page callback for Entity collection entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Entity collection templates.
 *
 * Default template: entity_collection.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_entity_collection(array &$variables) {

  /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection */
  $entityCollection = $variables['elements']['#entity_collection'];
  /** @var \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType */
  $entityCollectionType = Drupal::service('entity_type.manager')
    ->getStorage('entity_collection_type')
    ->load($entityCollection->bundle());

  if (!$entityCollectionType) {
    return;
  }

  $variables['attributes']['class'][] = 'js-entity-collection';
  $variables['attributes']['data-entity-collection-type'] = $entityCollectionType->id();
  $variables['attributes']['data-entity-collection-id'] = $entityCollection->id();

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}