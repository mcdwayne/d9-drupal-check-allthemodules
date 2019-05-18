<?php

namespace Drupal\entity_collector\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element\Link;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Class EntityCollectionLink.
 *
 * @RenderElement("entity_collection_link")
 *
 * @package Drupal\entity_collector\Element
 */
class EntityCollectionLink extends Link {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderLink'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderLink($element) {
    if (empty($element['#entityCollection'])) {
      return [];
    }

    /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection */
    $entityCollection = $element['#entityCollection'];
    /** @var \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType */
    $entityCollectionType = \Drupal::service('entity_collection.manager')->getEntityCollectionBundleType($entityCollection);

    $element['#title'] = $entityCollection->label();
    $element['#url'] = Url::fromRoute('entity_collector.set_active_collection', ['entityCollectionTypeId' => $entityCollectionType->id(), 'entityCollectionId' => $entityCollection->id()]);
    $element['#options']['attributes']['class'][] = 'use-ajax';
    $element['#options']['attributes']['class'][] = 'js-entity-collection';
    $element['#options']['attributes']['class'][] = 'entity-collection-' . $entityCollection->id();
    $element['#options']['attributes']['class'][] = 'entity-collection-type-' . $entityCollection->bundle();
    $element['#options']['attributes']['data-entity-collection-type'] = $entityCollection->bundle();
    $element['#options']['attributes']['data-entity-collection'] = $entityCollection->id();
    $element['#attached']['library'][] = 'core/drupal.ajax';

    return parent::preRenderLink($element);
  }

}
