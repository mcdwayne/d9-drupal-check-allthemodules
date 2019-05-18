<?php

namespace Drupal\service_description\Handler;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\service_description\Discovery\JsonDirectoryDiscovery;
use Guzzle\Service\Loader\JsonLoader;

/**
 * Provides the available service description based on json files.
 */
class ServiceDescriptionHandler implements ServiceDescriptionHandlerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The discovery class to find all files.
   *
   * @var \Drupal\Component\Discovery\DiscoverableInterface
   */
  protected $discovery;

  /**
   * Constructs a new PermissionHandler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets the discovery.
   *
   * @return \Drupal\Component\Discovery\DiscoverableInterface
   *   The discovery.
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new JsonDirectoryDiscovery($this->moduleHandler->getModuleDirectories(), 'description', 'service_description');
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescriptions() {
    return $this->getDiscovery()->findAll();
  }

}
