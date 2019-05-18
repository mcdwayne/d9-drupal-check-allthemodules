<?php

namespace Drupal\search_api_swiftype;

use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\Query\QueryInterface;

/**
 * Defines an interface for Swiftype search backend plugins.
 *
 * It extends the generic \Drupal\search_api\Backend\BackendInterface and covers
 * additional Swiftype specific methods.
 */
interface SwiftypeBackendInterface extends BackendInterface {

  /**
   * The Swiftype client service.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface
   *   The client service.
   */
  public function getClientService();

  /**
   * Get information about the connected Swiftype engine.
   *
   * @param bool $refresh
   *   (Optional) Whether to reload the engine data. Defaults to FALSE.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The Swiftype engine.
   */
  public function getEngineInfo($refresh = FALSE);

  /**
   * Create a SwiftypeDocument from a search_api ItemInterface.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search_api index.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   The search_api item.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface
   *   The Swiftype document.
   */
  public function createDocumentFromItem(IndexInterface $index, ItemInterface $item);

  /**
   * Create Swiftype documents from search_api items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search_api index.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   The search_api items.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface[]
   *   The Swiftype documents keyed by item ID.
   */
  public function createDocumentsFromItems(IndexInterface $index, array &$items = []);

  /**
   * Build sorts for the search query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search_api query.
   *
   * @return array
   *   Associative array containing "sort_fields" and "sort_direction" for every
   *   document type.
   */
  public function buildSorts(QueryInterface $query);

  /**
   * Build the search filters.
   *
   * @param \Drupal\search_api\Query\ConditionGroupInterface $condition_group
   *   The query condition group.
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to operate on.
   * @param array $document_type_fields
   *   List of fields in document type.
   *
   * @return array
   *   List of filter objects per document type.
   */
  public function buildFilters(ConditionGroupInterface $condition_group, IndexInterface $index, array $document_type_fields = []);

  /**
   * Build a filter for a single field.
   *
   * @param string $field
   *   Name of field.
   * @param mixed $value
   *   The filter value.
   * @param string $operator
   *   The filter operator.
   * @param \Drupal\search_api\Item\FieldInterface $index_field
   *   The index field.
   *
   * @return array
   *   The filter information for the field.
   */
  public function buildFilter($field, $value, $operator, FieldInterface $index_field);

}
