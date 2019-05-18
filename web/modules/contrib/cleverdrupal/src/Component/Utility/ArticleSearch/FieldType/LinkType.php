<?php

namespace Drupal\cleverreach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ObjectSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ComplexCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ObjectAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\UrlAttribute;
use Drupal\Core\Url;

/**
 * Link type support.
 */
class LinkType extends BaseField {

  /**
   * @inheritdoc
   */
  public function getSchemaField() {
    if ($this->isSingleValue()) {
      return new ObjectSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        $this->getAttributes()
      );
    }

    return new ComplexCollectionSchemaAttribute(
        $this->field->getName(),
        $this->field->getLabel(),
        $this->isSearchable(),
        $this->getSearchableConditions(),
        $this->getAttributes()
    );
  }

  /**
   * @inheritdoc
   */
  public function getSearchResultValue($node) {
    $code = $this->field->getName();
    $values = $node->get($code)->getValue();

    $attributes = [];
    foreach ($values as $value) {
      $attributes[] = new ObjectAttribute(
        $code, [
          new UrlAttribute('url', $this->getLinkUrl($value['uri'])),
          new TextAttribute('title', isset($value['title']) ? $value['title'] : ''),
        ]
      );
    }

    if ($this->isSingleValue()) {
      return isset($attributes[0]) ? $attributes[0] : new ObjectAttribute($code);
    }

    return new ComplexCollectionAttribute($code, $attributes);
  }

  /**
   * @return array List of sub attributes.
   */
  private function getAttributes() {
    return [
      new SimpleSchemaAttribute(
            'url',
            t('URL'),
            FALSE,
            [],
            SchemaAttributeTypes::URL
      ),
      new SimpleSchemaAttribute(
            'title',
            t('Title'),
            FALSE,
            [],
            SchemaAttributeTypes::TEXT
      ),
    ];
  }

  /**
   * Gets link URL by provided uri / url.
   *
   * @param int $uri
   *   Link URI.
   *
   * @return string Full link URL.
   */
  private function getLinkUrl($uri) {
    return Url::fromUri($uri)->setOption('absolute', TRUE)->toString();
  }

}
