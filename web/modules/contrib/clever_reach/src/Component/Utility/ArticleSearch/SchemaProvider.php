<?php

namespace Drupal\clever_reach\Component\Utility\ArticleSearch;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItems;
use Drupal\clever_reach\Component\Repository\ArticleRepository;
use Drupal\clever_reach\Exception\ContentTypeNotFoundException;

/**
 * Article search schema provider.
 */
class SchemaProvider {
  /**
   * Article repository instance.
   *
   * @var \Drupal\clever_reach\Component\Repository\ArticleRepository
   */
  private $articleRepository;

  /**
   * Gets all supported searchable items.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItems
   *   Object containing all searchable items supported by module.
   */
  public function getSearchableItems() {
    $searchableItems = new SearchableItems();
    $contentTypes = $this->getArticleRepository()->getContentTypes();

    foreach ($contentTypes as $code => $name) {
      $searchableItems->addSearchableItem(new SearchableItem($code, $name));
    }

    return $searchableItems;
  }

  /**
   * Gets list of supported searchable items from CleverReach system.
   *
   * All content types defined in Drupal are currently supported.
   *
   * @param string $contentType
   *   Type of content.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema|null
   *   Article schema.
   *
   * @throws ContentTypeNotFoundException
   */
  public function getSchema($contentType) {
    $schema = $this->getStaticSchema();
    $contentTypes = $this->getArticleRepository()->getContentTypes();

    if (!array_key_exists($contentType, $contentTypes)) {
      throw new ContentTypeNotFoundException("Content type '$contentType' is not found.");
    }

    $contentTypeFields = $this->getArticleRepository()->getFieldsByContentType('node', $contentType);

    /** @var \Drupal\Core\Field\BaseFieldDefinition $contentTypeField */
    foreach ($contentTypeFields as $contentTypeField) {
      if (!$field = SchemaFieldFactory::getField($contentTypeField)) {
        continue;
      }

      $schema[] = $field->getSchemaField();
    }

    return new SearchableItemSchema($contentType, $schema);
  }

  /**
   * Gets static schema.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttribute[]
   *   List of static schema attributes.
   */
  private function getStaticSchema() {
    return [
      new SimpleSchemaAttribute(
            'url',
            t('Url'),
            FALSE,
            [],
            SchemaAttributeTypes::URL
      ),
      new SimpleSchemaAttribute(
          'date',
          t('Date'),
          TRUE,
          [
            Conditions::EQUALS,
            Conditions::NOT_EQUAL,
            Conditions::GREATER_EQUAL,
            Conditions::GREATER_THAN,
            Conditions::LESS_EQUAL,
            Conditions::LESS_THAN,
          ],
          SchemaAttributeTypes::DATE
      ),
      new SimpleSchemaAttribute(
            'author',
            t('Author'),
          TRUE,
          [
            Conditions::EQUALS,
            Conditions::NOT_EQUAL,
          ],
            SchemaAttributeTypes::AUTHOR
      ),
      new SimpleSchemaAttribute(
            'mainImage',
            t('Main Image'),
            FALSE,
            [],
            SchemaAttributeTypes::IMAGE
      ),
      new SimpleSchemaAttribute(
            'articleHtml',
            t('Article HTML'),
            FALSE,
            [],
            SchemaAttributeTypes::HTML
      ),
    ];
  }

  /**
   * Gets article repository.
   *
   * @return \Drupal\clever_reach\Component\Repository\ArticleRepository
   *   Article repository instance.
   */
  private function getArticleRepository() {
    if (NULL === $this->articleRepository) {
      $this->articleRepository = new ArticleRepository();
    }

    return $this->articleRepository;
  }

}
