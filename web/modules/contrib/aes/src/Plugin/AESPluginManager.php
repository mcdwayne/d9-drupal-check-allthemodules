<?php

namespace Drupal\aes\Plugin;

//use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for AES.
 *
 */
class AESPluginManager extends DefaultPluginManager {

  /**
   * List of already instantiated plugins. All custom classes expects to be satisfied with having only single object.
   * Used the same approach as in MailManager.
   *
   * @var array
   */
  protected $instances = array();

  /**
   * Constructs a AESPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AES', $namespaces, $module_handler, 'Drupal\aes\Plugin\AESPluginBase', 'Drupal\aes\Annotation\Cryptor');
    $this->setCacheBackend($cache_backend, 'aes_encryptors');
    $this->mapper = 'Would be nice to use some standard Singleton here, but so far using own implementation.';
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $plugin_id = $options['id'];
    if (!isset($this->instances[$plugin_id])) {
      $configuration = $options;
      unset($configuration['id']);
      $this->instances[$plugin_id] = parent::createInstance($plugin_id, $configuration);
    }
    return $this->instances[$plugin_id];
  }

  /**
   * Simplified analog of getInstance().
   *
   * @param string $plugin_id
   *   The plugin ID.
   */
  public function getInstanceById($plugin_id) {
    if (!isset($this->instances[$plugin_id])) {
      $this->instances[$plugin_id] = parent::createInstance($plugin_id);
    }
    return $this->instances[$plugin_id];
  }
}
