<?php

namespace Drupal\search_api_swiftype;

use Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface;

/**
 * Defines the interface for a Swiftype entity factory.
 */
interface SwiftypeEntityFactoryInterface {

  /**
   * Create a new SwiftypeEngineInterface object.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface $client_service
   *   The Swiftype client service.
   * @param array $values
   *   (Optional) Values of the engine to create.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface
   *   The created engine object.
   */
  public function createEngine(SwiftypeClientInterface $client_service, array $values = []);

  /**
   * Create a new SwiftypeDocumentTypeInterface object.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface $client_service
   *   The Swiftype client service.
   * @param array $values
   *   (Optional) Values of the document type to create.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface
   *   The created document type object.
   */
  public function createDocumentType(SwiftypeClientInterface $client_service, array $values = []);

  /**
   * Create a new SwiftypeDocumentInterface object.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface $client_service
   *   The Swiftype client service.
   * @param array $values
   *   (Optional) Values of the document to create.
   *
   * @return \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface
   *   The created document object.
   */
  public function createDocument(SwiftypeClientInterface $client_service, array $values = []);

}
