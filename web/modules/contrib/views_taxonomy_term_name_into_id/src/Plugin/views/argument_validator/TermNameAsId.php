<?php

namespace Drupal\views_taxonomy_term_name_into_id\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\taxonomy\Plugin\views\argument_validator\TermName;

/**
 * Validates whether an argument is a term name, and if so, converts into the corresponding term ID.
 *
 * @ViewsArgumentValidator(
 *   id = "taxonomy_term_name_into_id",
 *   title = @Translation("Taxonomy term name as ID"),
 *   entity_type = "taxonomy_term"
 * )
 */
class TermNameAsId extends TermName {

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if ($this->options['transform']) {
      $argument = str_replace('-', ' ', $argument);
    }

    // If bundles is set then restrict the loaded terms to the given bundles.
    if (!empty($this->options['bundles'])) {
      $terms = $this->termStorage->loadByProperties(['name' => $argument, 'vid' => $this->options['bundles']]);
    }
    else {
      $terms = $this->termStorage->loadByProperties(['name' => $argument]);
    }

    // $terms are already bundle tested but we need to test access control.
    foreach ($terms as $term) {
      if ($this->validateEntity($term)) {
        // We only need one of the terms to be valid, so set the argument to
        // the term ID return TRUE when we find one.
        $this->argument->argument = $term->id();
        return TRUE;
        // @todo: If there are other values in $terms, maybe it'd be nice to
        // warn someone that there were multiple matches and we're only using
        // the first one.
      }
    }
    return FALSE;
  }

}
