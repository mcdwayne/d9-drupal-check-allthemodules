<?php

namespace Drupal\mail;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Mail message processor plugin manager.
 */
class MailMessageProcessorManager extends DefaultPluginManager {

  /**
   * Constructor for MailMessageProcessorManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/MailMessageProcessor', $namespaces, $module_handler, 'Drupal\mail\Plugin\MailMessageProcessorInterface', 'Drupal\mail\Annotation\MailMessageProcessor');

    $this->alterInfo('mail_mail_message_processor_info');
    $this->setCacheBackend($cache_backend, 'mail_mail_message_processor_plugins');
  }

}
