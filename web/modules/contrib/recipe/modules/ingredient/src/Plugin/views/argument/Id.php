<?php

namespace Drupal\ingredient\Plugin\views\argument;

use Drupal\ingredient\Entity\Ingredient;
use Drupal\views\Plugin\views\argument\NumericArgument;

/**
 * Argument handler to accept an ingredient id.
 *
 * @ViewsArgument("ingredient_id")
 */
class Id extends NumericArgument {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function titleQuery() {
    $titles = [];

    $ingredients = Ingredient::loadMultiple($this->value);
    foreach ($ingredients as $ingredient) {
      $titles[] = $ingredient->label();
    }
    return $titles;
  }

}
