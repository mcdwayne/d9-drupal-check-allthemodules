<?php

namespace Drupal\entity_reference_layout;

use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

/**
 * Defines a item list class for entity reference fields.
 */
class EntityReferenceLayoutRevisionsFieldItemList extends EntityReferenceRevisionsFieldItemList implements EntityReferenceFieldItemListInterface {

  /**
   * Set section ids for each item in field list.
   *
   * For each item in the list, section_id references
   * the entity (usually paragraph) to which layout data
   * is attached. Currently unused, this gives us the ability
   * in the future to derive exactly which layout a particular
   * entity is associated within.
   */
  public function preSave() {
    parent::preSave();
    $handler_settings = $this->getSetting('handler_settings');
    $layout_bundles = $handler_settings['layout_bundles'];
    if ($this->list) {
      $section_id = 0;
      foreach ($this->list as $delta => $item) {
        if (in_array($item->entity->bundle(), $layout_bundles)) {
          $section_id = $item->entity->id();
        }
        if ($section_id && $item->region) {
          $this->list[$delta]->section_id = $section_id;
        }
        else {
          $this->list[$delta]->section_id = 0;
        }
      }
    }
  }

}
