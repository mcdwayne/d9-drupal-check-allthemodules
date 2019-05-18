<?php

namespace Drupal\search_api_swiftype\SwiftypeDocumentType;

/**
 * Interface for Swiftype document type.
 */
interface SwiftypeDocumentTypeInterface {

  /**
   * Get the Swiftype engine the document type belongs to.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The Swiftype engine.
   *
   * @throws \Drupal\search_api_swiftype\Exception\EngineNotFoundException
   */
  public function getEngine();

  /**
   * Get the document type field mapping.
   *
   * @return array
   *   List of fields where the key is the field name and the value is the field
   *   data type.
   */
  public function getFieldMapping();

  /**
   * Find a document type by its name.
   *
   * @param string $name
   *   The name of the document type to find.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface
   *   The found document type.
   *
   * @throws \Drupal\search_api_swiftype\Exception\DocumentTypeNotFoundException
   */
  public function findByName($name);

  /**
   * Get the document type name.
   *
   * @return string
   *   The document type name.
   */
  public function getName();

  /**
   * Get the internal identifier of the document type.
   *
   * @return string
   *   The internal identifier of the document type (slug).
   */
  public function getSlug();

  /**
   * Get the internal document type key.
   *
   * @return string
   *   The internal document type key.
   */
  public function getKey();

  /**
   * Get the date the document type has been updated last.
   *
   * @return \DateTimeInterface
   *   Last update date of document type.
   */
  public function getUpdateDate();

  /**
   * Get the number of documents in the document type.
   *
   * @return int
   *   Number of documents in the index.
   */
  public function getDocumentCount();

  /**
   * Get raw document type data.
   *
   * @return array
   *   The raw data of the Swiftype document type.
   */
  public function getRawData();

  /**
   * Load a single Swiftype document types from the server.
   *
   * @param string $id
   *   The internal identifier of the document type.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface
   *   The loaded document type.
   */
  public function load($id);

  /**
   * Load multiple document types from the server.
   *
   * @param array $ids
   *   List of Ids to load. If empty, all document types are loaded.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface[]
   *   The loaded document types.
   */
  public function loadMultiple(array $ids = []);

  /**
   * Delete a single document type.
   *
   * @throws \Drupal\search_api_swiftype\Exception\SwiftypeException
   */
  public function delete();

}
