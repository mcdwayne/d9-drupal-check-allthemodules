<?php

namespace Drupal\regex_redirect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the redirect schema, based on the storage schema of contrib module.
 */
class RegexRedirectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add indexes.
    $schema['regex_redirect']['unique keys'] += [
      'hash' => ['hash'],
    ];
    $schema['regex_redirect']['indexes'] += [
      // Limit length to 191.
      'source_language' => [['regex_redirect_source', 191], 'language'],
    ];

    return $schema;
  }

}
