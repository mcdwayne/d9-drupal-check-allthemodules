<?php

namespace Drupal\dea;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class GrantDiscovery extends DefaultPluginManager implements GrantDiscoveryInterface {

  /**
   * @var GrantDiscoveryInterface[]
   */
  protected $discoveries = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/dea', $namespaces, $module_handler, '\Drupal\dea\GrantDiscoveryInterface', '\Drupal\dea\Annotation\GrantDiscovery');
    foreach ($this->getDefinitions() as $plugin_id => $info) {
      $this->discoveries[] = $this->createInstance($plugin_id);
    }
  }

  /**
   * @param AccountInterface $subject
   *   The entity to scan for operation requirements.
   * @param EntityInterface $target
   *   The target entity of the operation.
   * @param $operation
   *   The operation to be executed.
   *
   * @return EntityInterface[]
   *   List of entities that are granted to this account.
   */
  public function grants(AccountInterface $subject, EntityInterface $target, $operation) {
    $grants = [];
    foreach ($this->discoveries as $discovery) {
      foreach ($discovery->grants($subject, $target, $operation) as $grant) {
        if (!in_array($grant, $grants)) {
          $grants[] = $grant;
        }
      }
    }
    return $grants;
  }
}