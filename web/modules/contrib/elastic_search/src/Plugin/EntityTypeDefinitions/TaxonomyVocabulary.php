<?php

namespace Drupal\elastic_search\Plugin\EntityTypeDefinitions;

use Drupal\elastic_search\Annotation\EntityTypeDefinitions;

/**
 * We never actually map a vocab, but we do want to attach the term mapping to
 * a vocab page So we can do it just by extending the Term type.
 *
 * @EntityTypeDefinitions(
 *   id = "taxonomy_vocabulary",
 *   label = @Translation("Taxonomy Vocabulary")
 * )
 */
class TaxonomyVocabulary extends TaxonomyTerm {

  /**
   * @param string $entityType
   * @param string $bundleType
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  public function getFieldDefinitions(string $entityType, string $bundleType) {
    return parent::getFieldDefinitions('taxonomy_term', $bundleType);
  }

}
