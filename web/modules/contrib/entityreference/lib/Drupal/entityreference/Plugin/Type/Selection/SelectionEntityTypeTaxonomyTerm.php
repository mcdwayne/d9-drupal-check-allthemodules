<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\entityreference\selection\SelectionEntityTypeTaxonomyTerm.
 *
 * Provide entity type specific access control of the file entity type.
 */

namespace Drupal\entityreference\Plugin\Type\Selection;

use Drupal\Core\Entity\EntityFieldQuery;
use Drupal\Core\Database\Query\AlterableInterface;

use Drupal\entityreference\Plugin\entityreference\selection\SelectionBase;

class SelectionEntityTypeTaxonomyTerm extends SelectionBase {

  public function entityFieldQueryAlter(AlterableInterface $query) {
    // The Taxonomy module doesn't implement any proper taxonomy term access,
    // and as a consequence doesn't make sure that taxonomy terms cannot be viewed
    // when the user doesn't have access to the vocabulary.
    $tables = $query->getTables();
    $base_table = key($tables);
    $vocabulary_alias = $query->innerJoin('taxonomy_vocabulary', 'n', '%alias.vid = ' . $base_table . '.vid');
    $query->addMetadata('base_table', $vocabulary_alias);
    // Pass the query to the taxonomy access control.
    $this->reAlterQuery($query, 'taxonomy_vocabulary_access', $vocabulary_alias);

    // Also, the taxonomy term entity exposes a bundle, but doesn't have a bundle
    // column in the database. We have to alter the query ourself to go fetch
    // the bundle.
    $conditions = &$query->conditions();
    foreach ($conditions as $key => &$condition) {
      if ($key !== '#conjunction' && is_string($condition['field']) && $condition['field'] === 'vocabulary_machine_name') {
        $condition['field'] = $vocabulary_alias . '.machine_name';
        break;
      }
    }
  }
}
