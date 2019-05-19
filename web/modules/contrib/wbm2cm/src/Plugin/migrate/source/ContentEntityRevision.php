<?php

namespace Drupal\wbm2cm\Plugin\migrate\source;

/**
 * Loads certain fields from all revisions of all entities of a specific type.
 *
 * @MigrateSource(
 *   id = "content_entity_revision",
 *   deriver = "\Drupal\wbm2cm\Plugin\migrate\source\ContentEntityDeriver",
 * )
 */
class ContentEntityRevision extends ContentEntity {

  /**
   * {@inheritdoc}
   */
  protected function load() {
    $revisions = $this->storage->getQuery()->allRevisions()->execute();

    foreach (array_keys($revisions) as $vid) {
      yield $this->storage->loadRevision($vid);
    }
  }

}
