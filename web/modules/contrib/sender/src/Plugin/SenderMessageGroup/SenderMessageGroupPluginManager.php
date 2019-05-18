<?php

namespace Drupal\sender\Plugin\SenderMessageGroup;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Manager for message group plugins.
 */
class SenderMessageGroupPluginManager extends DefaultPluginManager {

  /**
   * A set of defaults to be referenced by $this->processDefinition().
   *
   * @var array
   */
  protected $defaults = [
    'label' => '',
    'token_types' => [],
    'class' => 'Drupal\sender\Plugin\SenderMessageGroup\MessageGroup',
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'sender_message_groups');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      // Uses YAML discovery.
      $discovery = new YamlDiscovery('sender_message_groups', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

}
