<?php

namespace Drupal\private_taxonomy;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines a class containing permission callbacks.
 */
class PrivateTaxonomyPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of vocabulary permissions.
   *
   * @return array
   *   Array of permissions.
   */
  public function vocabularyPermissions() {
    $permissions = [];
    foreach (Vocabulary::loadMultiple() as $vocabulary) {
      if (private_taxonomy_is_vocabulary_private($vocabulary->id())) {
        $permissions += [
          'edit own terms in ' . $vocabulary->id() => [
            'title' => $this->t('Edit own terms in %vocabulary',
              ['%vocabulary' => $vocabulary->label()]),
          ],
        ];
        $permissions += [
          'delete own terms in ' . $vocabulary->id() => [
            'title' => $this->t('Delete own terms from %vocabulary',
              ['%vocabulary' => $vocabulary->label()]),
          ],
        ];
      }
    }
    return $permissions;
  }

}
