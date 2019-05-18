<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;

/**
 * Number type support.
 */
class NumberType extends StringType {

  /**
   * @inheritdoc
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
