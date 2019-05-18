<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\EnumAttribute;

/**
 * Comment type support.
 */
class CommentType extends BaseField {

  /**
   * @inheritdoc
   */
  public function getSchemaField() {
    return new EnumSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        [new Enum(TRUE, '2'), new Enum(FALSE, '1')]
    );
  }

  /**
   * @inheritdoc
   */
  public function getSearchResultValue($node) {
    $code = $this->field->getName();
    $value = $node->get($code)->getValue();
    $status = isset($value[0]['status']) ? $value[0]['status'] : $node->get($code)->getString();
    return new EnumAttribute($code, $status);
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
