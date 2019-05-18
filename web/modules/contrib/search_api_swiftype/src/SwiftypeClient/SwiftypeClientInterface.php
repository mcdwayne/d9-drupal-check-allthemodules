<?php

namespace Drupal\search_api_swiftype\SwiftypeClient;

use Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface;
use Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface;

/**
 * Interface for SwiftypeClient implementations.
 */
interface SwiftypeClientInterface {

  /**
   * Set the key used to authenticate API requests.
   *
   * @param string $key
   *   The API key.
   */
  public function setApiKey($key);

  /**
   * Get the Swiftype entity factory.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEntityFactoryInterface
   *   The Swiftype entity factory.
   */
  public function getEntityFactory();

  /**
   * Confirm whether the client is authorized using the configured API key.
   */
  public function isAuthorized();

  /**
   * List every engine associated to the current account.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface[]
   *   List of Swiftype engines.
   */
  public function listEngines();

  /**
   * Create a new engine.
   *
   * @param string $name
   *   The name of the engine.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The created engine.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function createEngine($name);

  /**
   * Get a specific engine.
   *
   * @param string $slug
   *   The internal identifier (slug).
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The found engine.
   *
   * @throws \Drupal\search_api_swiftype\Exception\EngineNotFoundException
   */
  public function getEngine($slug);

  /**
   * Delete a Swiftype engine.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to delete.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function deleteEngine(SwiftypeEngineInterface $engine);

  /**
   * List all document types in an engine.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to operate on.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface[]
   *   List of document types.
   */
  public function listDocumentTypes(SwiftypeEngineInterface $engine);

  /**
   * Create a new document type.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to operate on.
   * @param string $name
   *   Name of the document type to create.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface
   *   The new document type.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function createDocumentType(SwiftypeEngineInterface $engine, $name);

  /**
   * Get a specific document type in an engine.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to operate on.
   * @param string $slug
   *   The internal identifier (slug) of the document type.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface
   *   The found document type.
   *
   * @throws \Drupal\search_api_swiftype\Exception\DocumentTypeNotFoundException
   */
  public function getDocumentType(SwiftypeEngineInterface $engine, $slug);

  /**
   * Delete a specific document type in an engine.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to operate on.
   * @param string $slug
   *   The internal identifier (slug) of the document type.
   *
   * @throws \Drupal\search_api_swiftype\Exception\DocumentTypeNotFoundException
   */
  public function deleteDocumentType(SwiftypeEngineInterface $engine, $slug);

  /**
   * Bulk create or update documents.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to operate on.
   * @param \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface $document_type
   *   The document type.
   * @param \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface[] $documents
   *   The documents to create or update.
   *
   * @return array
   *   Status of operation for each document.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function bulkCreateOrUpdateDocuments(SwiftypeEngineInterface $engine, SwiftypeDocumentTypeInterface $document_type, array $documents);

  /**
   * Delete multiple documents from a document type at once.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to operate on.
   * @param \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface $document_type
   *   The document type.
   * @param array $document_ids
   *   List of document Ids ("external_id") to delete.
   *
   * @return array
   *   Status of operation for each document.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function bulkDeleteDocuments(SwiftypeEngineInterface $engine, SwiftypeDocumentTypeInterface $document_type, array $document_ids);

  /**
   * Search for content.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface $engine
   *   The engine to search in.
   * @param array $data
   *   The data to search for.
   *
   * @return array
   *   The server response including:
   *   - record_count: Total number of results.
   *   - records: List of search results.
   *   - info: Information about facets and results keyed by document type.
   *   - errors: List of errors.
   */
  public function search(SwiftypeEngineInterface $engine, array $data = []);

}
