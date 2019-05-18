<?php

namespace Drupal\domain_path_redirect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the redirect schema.
 */
class DomainPathRedirectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add indexes.
    $schema['domain_path_redirect']['unique keys'] += [
      'hash' => ['hash'],
    ];
    $schema['domain_path_redirect']['indexes'] += [
      // Limit length to 191.
      'source_language' => [['redirect_source__path', 191], 'language'],
    ];

    return $schema;
  }

}
