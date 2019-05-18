<?php

namespace Drupal\search_api_swiftype;

use Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface;
use Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocument;
use Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentType;
use Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngine;

/**
 * Defines the Swiftype entity factory.
 */
class SwiftypeEntityFactory implements SwiftypeEntityFactoryInterface {

  /**
   * {@inheritdoc}
   */
  public function createEngine(SwiftypeClientInterface $client_service, array $values = []) {
    return new SwiftypeEngine($client_service, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function createDocumentType(SwiftypeClientInterface $client_service, array $values = []) {
    return new SwiftypeDocumentType($client_service, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function createDocument(SwiftypeClientInterface $client_service, array $values = []) {
    return new SwiftypeDocument($client_service, $values);
  }

}
