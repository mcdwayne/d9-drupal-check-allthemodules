<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;
use Drupal\taxonomy\Entity\Term;

/**
 * Taxonomy type support.
 */
class TaxonomyType extends BaseField {

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
   */
  public function getSchemaField() {
    if ($this->isSingleValue()) {
      return new SimpleSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        SchemaAttributeTypes::TEXT
      );
    }

    return new SimpleCollectionSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        SchemaAttributeTypes::TEXT
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

    $attributes = [];
    foreach ($node->get($code)->getValue() as $value) {
      if (!$term = $this->getTermById($value['target_id'])) {
        continue;
      }

      $attributes[] = new TextAttribute($code, $term->getName());
    }

    if ($this->isSingleValue()) {
      return isset($attributes[0]) ? $attributes[0] : new TextAttribute($code, '');
    }

    return new SimpleCollectionAttribute($code, $attributes);
  }

  /**
   * Get term by Id.
   *
   * @param int $id
   *   Id of Term.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   Term object.
   */
  private function getTermById($id) {
    return Term::load($id);
  }

}
