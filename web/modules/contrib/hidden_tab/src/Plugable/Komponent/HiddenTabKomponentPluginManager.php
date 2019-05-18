<?php

namespace Drupal\hidden_tab\Plugable\Komponent;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabKomponentAnon;
use Drupal\hidden_tab\Plugable\HiddenTabPluginManager;

/**
 * The plugin manager.
 *
 * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface
 */
class HiddenTabKomponentPluginManager extends HiddenTabPluginManager {

  protected $pid = HiddenTabKomponentInterface::PID;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/HiddenTabKomponent',
      $namespaces,
      $module_handler,
      HiddenTabKomponentInterface::class,
      HiddenTabKomponentAnon::class
    );
    $this->alterInfo('hidden_tab_komponent_info');
    $this->setCacheBackend($cache_backend, 'hidden_tab_komponent_plugin');
  }

  /**
   * All the komponents a plugin provides.
   *
   * @param string|null $id
   *   Id of the plugin in question.
   *
   * @return array
   *   All the komponents a plugin provides.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface::komponents()
   */
  public function komponentsOfPlugin(string $id): array {
    /** @noinspection PhpUndefinedMethodInspection */
    return $this->plugin($id)->komponents();
  }

  /**
   * Facory method, create an instance from container.
   *
   * @return \Drupal\hidden_tab\Plugable\HiddenTabPluginManager
   */
  public static function instance(): HiddenTabPluginManager {
    return \Drupal::service('plugin.manager.' . HiddenTabKomponentInterface::PID);
  }

}
