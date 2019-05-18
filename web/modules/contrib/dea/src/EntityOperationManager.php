<?php

namespace Drupal\dea;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provide operation definitions.
 */
class EntityOperationManager {
  /**
   * @var EntityTypeManagerInterface $typeManager
   */
  protected $typeManager;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var array
   */
  protected $entity_operations = [];

  /**
   * EntityOperationManager constructor.
   */
  public function __construct(EntityTypeManagerInterface $type_manager, ModuleHandlerInterface $module_handler) {
    $this->typeManager = $type_manager;
    $this->moduleHandler = $module_handler;
    $yaml_discovery = new YamlDiscovery('entity.operations', $this->moduleHandler->getModuleDirectories());
    $yaml_discovery->addTranslatableProperty('verb', 'entity_operation');

    $opts = ['context' => 'entity.operation'];
    foreach ($this->typeManager->getDefinitions() as $definition) {
      foreach (['view', 'update', 'delete'] as $op) {
        $this->entity_operations[$definition->id()][$op] = t($op, $opts);
      }
    }

    foreach ($yaml_discovery->getDefinitions() as $definition) {
      $this->entity_operations[$definition['entity_type']][$definition['operation']] = $definition['verb'];
    }
  }


  /**
   * {@inheritdoc}
   */
  public function allOperations() {
    return $this->entity_operations;
  }

  /**
   * {@inheritdoc}
   */
  public function operations($entity_type) {
    if (array_key_exists($entity_type, $this->entity_operations)) {
      return $this->entity_operations[$entity_type];
    }
    return [];
  }

}
