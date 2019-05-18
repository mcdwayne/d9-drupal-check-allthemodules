<?php

namespace Drupal\elastic_search\Plugin\EntityTypeDefinitions;

use Drupal\elastic_search\Annotation\EntityTypeDefinitions;
use Drupal\elastic_search\Plugin\EntityTypeDefinitionsBase;

/**
 *
 * @EntityTypeDefinitions(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy Term")
 * )
 */
class TaxonomyTerm extends EntityTypeDefinitionsBase {

  use FieldFilterTrait;

  /**
   * @inheritDoc
   */
  protected function allowedFields(): array {
    return ['name', 'description', '/field_\w*/'];
  }

}
