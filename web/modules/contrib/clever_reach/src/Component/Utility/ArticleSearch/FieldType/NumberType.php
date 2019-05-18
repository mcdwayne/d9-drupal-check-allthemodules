<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;

/**
 * Number type support.
 */
class NumberType extends StringType {

  /**
   * Gets list of searchable conditions.
   *
   * @see \CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions
   *
   * @return array
   *   Gets supported searchable conditions.
   */
  protected function getSearchableConditions() {
    return [
      Conditions::EQUALS,
      Conditions::NOT_EQUAL,
      Conditions::GREATER_THAN,
      Conditions::GREATER_EQUAL,
      Conditions::LESS_THAN,
      Conditions::LESS_EQUAL,
    ];
  }

}
