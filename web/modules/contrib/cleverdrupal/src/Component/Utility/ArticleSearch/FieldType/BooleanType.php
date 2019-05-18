<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\EnumAttribute;

/**
 * Boolean type support.
 */
class BooleanType extends BaseField {

  /**
   * @inheritdoc
   */
  public function getSchemaField() {
    return new EnumSchemaAttribute(
          $this->field->getName(),
          $this->field->getLabel(),
          $this->isSearchable(),
          $this->getSearchableConditions(),
          [new Enum(TRUE, '1'), new Enum(FALSE, '0')]
      );
  }

  /**
   * @inheritdoc
   */
  public function getSearchResultValue($node) {
    $code = $this->field->getName();
    return new EnumAttribute($code, $node->get($code)->getString());
  }

  /**
   * @inheritdoc
   */
  protected function getSearchableConditions() {
    return [
      Conditions::EQUALS,
      Conditions::NOT_EQUAL,
    ];
  }

  /**
   * @inheritdoc
   */
  protected function isSearchable() {
    return TRUE;
  }

}
