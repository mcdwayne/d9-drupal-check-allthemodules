<?php

namespace Drupal\clever_reach\Controller\Api;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\FilterParser;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\AuthorAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\HtmlAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\ImageAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\UrlAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Validator;
use DateTime;
use Drupal\clever_reach\Component\Utility\ArticleSearch\SchemaFieldFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * V1 Search item controller.
 */
class CleverreachSearchItemsController extends CleverreachBaseSearchController {
  /**
   * List of CleverReach - Drupal search condition mappings.
   */
  const CONDITION_MAPPING = [
    Conditions::EQUALS => '=',
    Conditions::NOT_EQUAL => '<>',
    Conditions::GREATER_THAN => '>',
    Conditions::LESS_THAN => '<',
    Conditions::LESS_EQUAL => '<=',
    Conditions::GREATER_EQUAL => '>=',
    Conditions::CONTAINS => 'CONTAINS',
  ];
  /**
   * Field specific mapping.
   */
  const FIELD_MAPPING = [
    'id' => 'nid',
    'date' => 'created',
    'itemCode' => 'type',
    'author' => 'uid',
  ];
  /**
   * Language used for search.
   *
   * @var string|null
   */
  private $language;

  /**
   * Gets list of search results performed by CleverReach system.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return string
   *   JSON string.
   */
  public function get(Request $request) {
    $id = $request->get('id');
    $contentType = $request->get('type');
    $rawFilter = $request->get('filter');

    try {
      if ($id === NULL) {
        $articleFilter = $this->getFilteredArticles($contentType, $rawFilter);
      }
      else {
        $articleFilter = [
            [
              'field' => 'nid',
              'value' => $id,
              'condition' => '=',
            ],
            [
              'field' => 'type',
              'value' => $contentType,
              'condition' => '=',
            ],
        ];
      }

      $result = $this->getSearchResult($contentType, $articleFilter)->toArray();
    }
    catch (\Exception $e) {
      $result = ['status' => 'error', 'message' => $e->getMessage()];
    }

    return new JsonResponse($result);
  }

  /**
   * Gets search result and applies all sent filters.
   *
   * @param string $contentType
   *   Content type retrieved via GET.
   * @param array $filter
   *   Parsed filter in associative array.
   *
   * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult
   *   Result object, containing all information about matched articles.
   */
  private function getSearchResult($contentType, array $filter) {
    $searchResult = new SearchResult();
    $articles = $this->getArticleRepository()->get($filter, $this->language);

    if (empty($articles)) {
      return $searchResult;
    }

    foreach ($articles as $article) {
      $author = $article->getOwner()->getDisplayName();
      $htmlContent = $this->getArticleRepository()->getContentByArticle($article, $this->language);
      $articleUrl = $this->getArticleRepository()->getUrlById($article, $this->language);

      $attributes = [
        new AuthorAttribute('author', $author),
        new UrlAttribute('url', $articleUrl),
        new HtmlAttribute('articleHtml', $htmlContent),
        new ImageAttribute('mainImage', ''),
      ];

      $contentTypeFields = $this->getArticleRepository()->getFieldsByContentType('node', $contentType);

      /** @var \Drupal\Core\Field\BaseFieldDefinition $contentTypeField */
      foreach ($contentTypeFields as $code => $contentTypeField) {
        if (!$field = SchemaFieldFactory::getField($contentTypeField)) {
          continue;
        }

        if (!$fieldValue = $field->getSearchResultValue($article)) {
          continue;
        }

        $attributes[] = $fieldValue;
      }

      $searchResult->addSearchResultItem(
        new SearchResultItem(
            $contentType,
            $article->id(),
            $article->getTitle(),
            $this->timestampToDate($article->getCreatedTime()),
            $attributes
        )
      );
    }

    return $searchResult;
  }

  /**
   * Converts provided timestamp to DateTime object.
   *
   * @param int $timestamp
   *   Unix timestamp.
   *
   * @return \DateTime
   *   Converted object from timestamp.
   */
  private function timestampToDate($timestamp) {
    return (new DateTime())->setTimestamp($timestamp);
  }

  /**
   * Gets list of matching articles by content type and sent filter.
   *
   * @param string $contentType
   *   Content type retrieved via GET.
   * @param string $rawFilter
   *   Url encoded filter retrieved via GET. Example: title ct 'great'.
   *
   * @return \Drupal\node\Entity\Node[]
   *   List of matching articles in Drupal.
   *
   * @throws \CleverReach\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching
   */
  private function getFilteredArticles($contentType, $rawFilter) {
    $schema = $this->getSchemaProvider()->getSchema($contentType);

    $filterParser = new FilterParser();
    $filters = $filterParser->generateFilters($contentType, NULL, urlencode($rawFilter));

    $filterValidator = new Validator();
    $filterValidator->validateFilters($filters, $schema);

    $filterBy = [];
    foreach ($filters as $filter) {
      if ($filter->getAttributeCode() === 'langcode') {
        $this->language = $filter->getAttributeValue();
        continue;
      }

      if (array_key_exists($filter->getAttributeCode(), self::FIELD_MAPPING)) {
        $fieldCode = self::FIELD_MAPPING[$filter->getAttributeCode()];
      }
      else {
        $fieldCode = $filter->getAttributeCode();
      }

      $fieldValue = $filter->getAttributeValue();
      $condition = self::CONDITION_MAPPING[$filter->getCondition()];

      if ($date = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $filter->getAttributeValue())) {
        $fieldValue = $date->getTimestamp();
      }

      $filterBy[] = [
        'field' => $fieldCode,
        'value' => $fieldValue,
        'condition' => $condition,
      ];
    }

    return $filterBy;
  }

}
