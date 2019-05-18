<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\EnumAttribute;

/**
 * Language type support.
 */
class LanguageType extends BaseField {

  /**
   * @inheritdoc
   */
  public function getSchemaField() {
    $languages = [];
    foreach (\Drupal::languageManager()->getLanguages() as $language) {
      $languages[] = new Enum($language->getName(), $language->getId());
    }

    return new EnumSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        $languages
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
    ];
  }

  /**
   * @inheritdoc
   */
  protected function isSearchable() {
    return TRUE;
  }

}
