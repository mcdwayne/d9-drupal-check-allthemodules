<?php

/**
 * @file
 * Contains \Drupal\retriever\Retriever\EntityHandler.
 */

namespace Drupal\retriever\Retriever;

use BartFeenstra\DependencyRetriever\Exception\UnknownDependencyException;
use BartFeenstra\DependencyRetriever\Retriever\Retriever;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an entity type handler retriever.
 */
class EntityTypeHandler implements Retriever {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'drupalEntityTypeHandler';
  }

  /**
   * Extracts handler information from the dependency ID.
   *
   * @param string $dependency_id
   *   The dependency ID.
   *
   * @return mixed[]
   *   Items are:
   *   - entity_type_id (string): an entity type ID.
   *   - type (string): the handler type's machine name.
   *   - operation (string|null): the name of the handler's operation, or NULL
   *     if the handler does not support operations.
   *
   * @throws \BartFeenstra\DependencyRetriever\Exception\UnknownDependencyException
   */
  protected function extractHandlerInfo($dependency_id) {
    if (!preg_match('/^[^.]+\.[^.]+(\.[^.]+){0,1}$/', $dependency_id)) {
      throw new UnknownDependencyException($this->getName(), $dependency_id);
    }

    return array_combine([
      'entity_type_id',
      'type',
      'operation'
    ], array_pad(explode('.', $dependency_id), 3, NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function knowsDependency($id) {
    try {
      $handler_info = $this->extractHandlerInfo($id);
      $entity_type = $this->entityTypeManager->getDefinition($handler_info['entity_type_id']);
      $nested = is_null($handler_info['operation']) ? FALSE : $handler_info['operation'];

      return $entity_type->hasHandlerClass($handler_info['type'], $nested);
    }
    catch (UnknownDependencyException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDependency($id) {
    $handler_info = $this->extractHandlerInfo($id);
    try {
      if ($handler_info['type'] === 'form') {
        return $this->entityTypeManager->getFormObject($handler_info['entity_type_id'], $handler_info['operation']);
      }
      return $this->entityTypeManager->getHandler($handler_info['entity_type_id'], $handler_info['type']);
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new UnknownDependencyException($this->getName(), $id, $e);
    }
  }
}
