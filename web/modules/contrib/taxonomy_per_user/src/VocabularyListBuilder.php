<?php

namespace Drupal\taxonomy_per_user;

use Drupal\taxonomy\VocabularyListBuilder as VocabularyListBuilderBase;

/**
 * Class VocabularyListBuilder.
 *
 * @package Drupal\gvocab
 */
class VocabularyListBuilder extends VocabularyListBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    // Remove vocabularies the current user doesn't have any access for.
    foreach ($entities as $id => $entity) {
      if (!TaxonomyPerUserAccessCheck::checkCreatorAccess('List', $id)->isAllowed()) {
        unset($entities[$id]);
      }
    }

    return $entities;
  }

}
