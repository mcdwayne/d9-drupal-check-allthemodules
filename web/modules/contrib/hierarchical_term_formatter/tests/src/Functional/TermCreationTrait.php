<?php

namespace Drupal\Tests\hierarchical_term_formatter\Functional;

/**
 * Provides convenience method for creating terms.
 */
trait TermCreationTrait {

  /**
   * Recursive function used to create terms in a tree.
   */
  protected function createTerms(array $items, $parent_id = 0) {
    foreach ($items as $key => $item) {
      $name = (is_array($item)) ? $key : $item;
      $term = $this->container->get('entity.manager')->getStorage('taxonomy_term')->create([
        'name' => $name,
        'vid' => 'numbers',
      ]);
      if ($parent_id) {
        $term->set('parent', $parent_id);
      }
      $term->save();
      $this->createdTerms[$term->label()] = $term->id();
      if (is_array($item)) {
        $this->createTerms($item, $term->id());
      }
    }
  }

}
