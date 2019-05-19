<?php

namespace Drupal\ssf;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the ssf_wordlist schema handler.
 */
class WordlistStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['ssf_wordlist']['unique keys'] += [
      'ssf_wordlist_field__token_value' => ['token'],
    ];

    return $schema;
  }

}
