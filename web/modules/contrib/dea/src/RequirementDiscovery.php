<?php

namespace Drupal\dea;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Entity\EntityInterface;

class RequirementDiscovery extends DefaultPluginManager implements RequirementDiscoveryInterface{

  /**
   * @var RequirementDiscoveryInterface[]
   */
  protected $discoveries = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/dea', $namespaces, $module_handler, '\Drupal\dea\RequirementDiscoveryInterface', '\Drupal\dea\Annotation\RequirementDiscovery');
    foreach ($this->getDefinitions() as $plugin_id => $info) {
      $this->discoveries[] = $this->createInstance($plugin_id);
    }
  }

  /**
   * @param EntityInterface $subject
   *   The entity to scan for operation requirements.
   * @param EntityInterface $target
   *   The target entity of the operation.
   * @param $operation
   *   The operation to be executed.
   *
   * @return EntityInterface[]
   *   List of entities that are required to execute the operation.
   */
  public function requirements(EntityInterface $subject, EntityInterface $target, $operation) {
    $requirements = [];
    foreach ($this->discoveries as $discovery) {
      foreach ($discovery->requirements($subject, $target, $operation) as $requirement) {
        if (!in_array($requirement, $requirements)) {
          $requirements[] = $requirement;
        }
      }
    }
    return $requirements;
  }
}