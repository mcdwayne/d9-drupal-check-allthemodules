<?php

namespace Drupal\search_api_swiftype\SwiftypeDocumentType;

use Drupal\search_api_swiftype\Exception\DocumentTypeNotFoundException;
use Drupal\search_api_swiftype\Exception\EngineNotFoundException;
use Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface;
use Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface;
use Drupal\search_api_swiftype\SwiftypeEntity;

/**
 * Defines a SwiftypeDocumentType.
 */
class SwiftypeDocumentType extends SwiftypeEntity implements SwiftypeDocumentTypeInterface {

  /**
   * Create a new SwiftypeDocumentType object.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface $client_service
   *   The Swiftype client service.
   * @param array $values
   *   (Optional) Values to create the document type from.
   */
  public function __construct(SwiftypeClientInterface $client_service, array $values = []) {
    parent::__construct($client_service);
    $this->data = $values + [
      'id' => '',
      'name' => '',
      'slug' => '',
      'engine_id' => '',
      'updated_at' => '',
      'document_count' => 0,
      'field_mapping' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEngine() {
    // Expand engine_id into fully loaded SwiftypeEngineInterface.
    $engine_id = $this->data['engine_id'];
    $engines = array_filter($this->clientService->listEngines(), function (SwiftypeEngineInterface $engine) use ($engine_id) {
      return $engine_id === $engine->getId();
    });

    if (empty($engines)) {
      throw new EngineNotFoundException($this->t('No engine found with id @id.', ['@id' => $engine_id]));
    }
    return reset($engines);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping() {
    return (array) $this->data['field_mapping'];
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return $this->loadMultiple([$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []) {
    // Load all document types.
    $document_types = $this->clientService->listDocumentTypes($this->getEngine());
    if (empty($ids)) {
      return $document_types;
    }
    // Filter engines by id (slug).
    return array_intersect_key($document_types, array_flip($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->clientService->deleteDocumentType($this->getEngine(), $this->getSlug());
  }

  /**
   * {@inheritdoc}
   */
  public function findByName($name) {
    // Load all document types.
    $document_types = $this->loadMultiple();
    $keys = array_keys(array_column($document_types, 'name'), $name, TRUE);
    if (empty($keys)) {
      throw new DocumentTypeNotFoundException($this->t("Record not found. No entity with id '@name'", ['@name' => $name]));
    }
    $first = reset($keys);
    return array_values($document_types)[$first];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->data['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSlug() {
    return $this->data['slug'];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->data['key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateDate() {
    return new \DateTime($this->data['updated_at']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentCount() {
    return $this->data['document_count'];
  }

}
