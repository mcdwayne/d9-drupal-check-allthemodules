<?php

namespace Drupal\group_taxonomy;

use Drupal\taxonomy\VocabularyListBuilder as VocabularyListBuilderBase;

/**
 * Class VocabularyListBuilder.
 *
 * @package Drupal\group_taxonomy
 */
class VocabularyListBuilder extends VocabularyListBuilderBase {

  /**
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function load() {
    $entities = parent::load();
    // Remove vocabularies the current user doesn't have any access for.
    foreach ($entities as $id => $entity) {
      if (!GroupTaxonomyAccessCheck::checkCreatorAccess('list terms', $id)) {
        unset($entities[$id]);
      }
    }

    return $entities;
  }

}
