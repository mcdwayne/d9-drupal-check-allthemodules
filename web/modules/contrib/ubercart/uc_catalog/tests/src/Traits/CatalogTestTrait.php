<?php

namespace Drupal\Tests\uc_catalog\Traits;

use Drupal\Core\Language\Language;
use Drupal\taxonomy\Entity\Term;

/**
 * Utility functions to provide catalog taxonomy terms for test purposes.
 *
 * This trait can only be used in classes which already use
 * RandomGeneratorTrait. RandomGeneratorTrait is used in all
 * the PHPUnit and Simpletest base classes.
 */
trait CatalogTestTrait {

  /**
   * Returns a new term with random properties in the catalog vocabulary.
   *
   * @param array $values
   *   Array of values to override the default term values.
   */
  protected function createCatalogTerm(array $values = []) {
    $term = Term::create($values + [
      'name' => $this->randomMachineName(),
      'description' => [
        'value' => $this->randomMachineName(),
        'format' => 'plain_text',
      ],
      'vid' => 'catalog',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();

    return $term;
  }

}
