<?php

namespace Drupal\dea;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class SolutionDiscovery extends DefaultPluginManager implements SolutionDiscoveryInterface {
  /**
   * @var SolutionDiscoveryInterface[]
   */
  protected $discoveries = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/dea', $namespaces, $module_handler, '\Drupal\dea\SolutionDiscoveryInterface', '\Drupal\dea\Annotation\SolutionDiscovery');
    foreach ($this->getDefinitions() as $plugin_id => $info) {
      $this->discoveries[] = $this->createInstance($plugin_id);
    }
  }

  /**
   * @param EntityInterface $subject
   *   The entity to scan for operation requirements.
   * @param AccountInterface $account
   *   The actor of the operation.
   * @param $operation
   *   The operation to be executed.
   *
   * @return EntityInterface[]
   *   List of entities that are required to execute the operation.
   */
  public function solutions(EntityInterface $subject, AccountInterface $account, $operation) {
    $solutions = [];
    foreach ($this->discoveries as $discovery) {
      foreach ($discovery->solutions($subject, $account, $operation) as $solution) {
        if (!in_array($solution, $solutions)) {
          $key = $solution->__toString();
          $solutions[$key] = $solution;
        }
      }
    }
    return array_unique($solutions);
  }
}