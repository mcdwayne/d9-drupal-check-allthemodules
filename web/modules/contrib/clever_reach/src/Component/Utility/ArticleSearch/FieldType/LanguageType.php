<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\EnumAttribute;

/**
 * Language type support.
 */
class LanguageType extends BaseField {

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
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
   * Converts to search result object from Drupal's object base on type.
   *
   * @param \Drupal\node\Entity\Node|null $node
   *   Drupal content type object.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleAttribute
   *   CleverReach search result attribute.
   */
  public function getSearchResultValue($node) {
    $code = $this->field->getName();
    return new EnumAttribute($code, $node->get($code)->getString());
  }

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
    ];
  }

  /**
   * Indicates whether field is searchable or not.
   *
   * @return bool
   *   If field searchable, return true, otherwise false.
   */
  protected function isSearchable() {
    return TRUE;
  }

}
