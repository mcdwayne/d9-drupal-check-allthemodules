<?php

namespace Drupal\search_api_swiftype\SwiftypeDocument;

use Drupal\search_api_swiftype\SwiftypeEntityInterface;

/**
 * Interface for Swiftype documents.
 */
interface SwiftypeDocumentInterface extends SwiftypeEntityInterface {

  /**
   * Get the external document ID.
   *
   * @return string
   *   The documents external_id property.
   */
  public function getExternalId();

  /**
   * Set the external document ID.
   *
   * @param string $id
   *   The documents external_id property.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface
   *   The document object for chaining.
   */
  public function setExternalId($id);

  /**
   * Get the documents field list.
   *
   * @return array
   *   List of fields containing an object with the following properties:
   *   - name: name of field.
   *   - value: value of field.
   *   - type: the fields type.
   */
  public function getFields();

  /**
   * Add a field to the document.
   *
   * @param string $name
   *   Name of field to add. If it already exists in the document, it overrides
   *   the existing field.
   * @param mixed $value
   *   The fields value.
   * @param string $type
   *   The field type.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface
   *   The document object for chaining.
   */
  public function addField($name, $value = NULL, $type = 'string');

  /**
   * Remove a field from the document.
   *
   * @param string $name
   *   The field to remove.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface
   *   The document object for chaining.
   */
  public function removeField($name);

}
