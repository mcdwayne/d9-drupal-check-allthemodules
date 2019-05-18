<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch\FieldType;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ObjectSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ComplexCollectionAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ObjectAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\UrlAttribute;
use Drupal\file\Entity\File;

/**
 * Image type support.
 */
class ImageType extends BaseField {

  /**
   * Gets schema field converted to CleverReach SchemaAttribute.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute
   *   CleverReach schema attribute.
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
    $values = $node->get($code)->getValue();

    $attributes = [];
    foreach ($values as $value) {
      $attributes[] = new ObjectAttribute(
        $code, [
          new UrlAttribute('url', $this->getImageUrl($value['target_id'])),
          new TextAttribute('alt', $value['alt']),
          new TextAttribute('title', $value['title']),
        ]
      );
    }

    if ($this->isSingleValue()) {
      return isset($attributes[0]) ? $attributes[0] : new ObjectAttribute($code);
    }

    return new ComplexCollectionAttribute($code, $attributes);
  }

  /**
   * Gets sub attributes of main image type.
   *
   * @return array
   *   List of sub attributes.
   */
  private function getAttributes() {
    return [
      new SimpleSchemaAttribute(
            'url',
            t('URI'),
            FALSE,
            [],
            SchemaAttributeTypes::URL
      ),
      new SimpleSchemaAttribute(
            'alt',
            t('Alt'),
            FALSE,
            [],
            SchemaAttributeTypes::TEXT
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
   * Gets image URL by provided image ID.
   *
   * @param int $imageId
   *   Unique file ID.
   *
   * @return string
   *   Full image URL with public access.
   */
  private function getImageUrl($imageId) {
    $file = File::load($imageId);

    if ($file === NULL) {
      return '';
    }

    return file_create_url($file->getFileUri());
  }

}
