<?php

/**
 * @file
 * Hooks provided by the Search API Swiftype module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter Swiftype documents before they are sent to the server for indexing.
 *
 * @param \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface[] $documents
 *   List of Swiftype documents prepared for indexing keyed by corresponding
 *   item ID.
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index for which items are being indexed.
 * @param \Drupal\search_api\Item\ItemInterface[] $items
 *   The original array of items to be indexed, keyed by their item IDs.
 */
function hook_search_api_swiftype_documents_alter(array &$documents, \Drupal\search_api\IndexInterface $index, array $items) {
  // Adds a "foo" field with value "bar" to all documents.
  foreach ($documents as $document) {
    $document->addField('foo', 'bar');
  }
}

/**
 * Alter the list of items to be deleted from the server.
 *
 * @param array $items
 *   Associative array of tracker items keyed by index_id containing the item ID
 *   and the datasource ID.
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index for which items are being indexed.
 */
function hook_search_api_swiftype_items_deleted_alter(array &$items, IndexInterface $index) {
  foreach ($items as $item) {
    // Log deletion.
    \Drupal::logger('search_api_swiftype')->info(t('Removed indexed item.'));
  }
}

/**
 * Change the way the index's field names are mapped to Swiftype field names.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The index whose field mappings are altered.
 * @param array $fields
 *   An associative array containing the index field names mapped to their
 *   Swiftype counterparts.
 */
function hook_search_api_swiftype_field_mapping_alter(\Drupal\search_api\IndexInterface $index, array &$fields) {
  if (isset($fields[$index->id()]['entity:node|body'])) {
    $fields[$index->id()]['entity:node|body'] = 's_body_value';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
