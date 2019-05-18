<?php

namespace Drupal\search_api_swiftype\SwiftypeEngine;

use Drupal\Core\Url;
use Drupal\search_api_swiftype\Exception\EngineNotFoundException;
use Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface;
use Drupal\search_api_swiftype\SwiftypeEntity;

/**
 * Defines a Swiftype engine.
 */
class SwiftypeEngine extends SwiftypeEntity implements SwiftypeEngineInterface {

  /**
   * Constructs a SwiftypeEngine object.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeClient\SwiftypeClientInterface $client_service
   *   The Swiftype client service.
   * @param array $values
   *   (Optional) Values to create the engine from.
   */
  public function __construct(SwiftypeClientInterface $client_service, array $values = []) {
    parent::__construct($client_service);
    $this->data = $values + [
      'id' => '',
      'name' => '',
      'slug' => '',
      'key' => '',
      'id' => '',
      'updated_at' => '',
      'document_count' => 0,
    ];
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
  public function loadMultiple(array $ids = [], $refresh = FALSE) {
    // Load all engines.
    $engines = $this->clientService->listEngines($refresh);
    if (empty($ids)) {
      return $engines;
    }
    // Filter engines by id (slug).
    return array_intersect_key($engines, array_flip($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->clientService->deleteEngine($this);
  }

  /**
   * {@inheritdoc}
   */
  public function findByName($name) {
    // Load all engines (there is no way to find an engine by name directly).
    $engines = $this->loadMultiple();
    $keys = array_keys(array_column($engines, 'name'), $name, TRUE);
    if (empty($keys)) {
      throw new EngineNotFoundException($this->t("Record not found. No entity with id '@name'", ['@name' => $name]));
    }
    $first = reset($keys);
    return array_values($engines)[$first];
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

  /**
   * {@inheritdoc}
   */
  public function getUrl(array $options = []) {
    return Url::fromUri('https://app.swiftype.com/engines/' . $this->getSlug() . '/overview', $options);
  }

}
