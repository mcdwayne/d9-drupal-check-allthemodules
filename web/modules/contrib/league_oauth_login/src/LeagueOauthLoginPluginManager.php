<?php

namespace Drupal\league_oauth_login;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * League_oauth_login plugin manager.
 */
class LeagueOauthLoginPluginManager extends DefaultPluginManager {

  /**
   * Constructs LeagueOauthLoginPluginManager object.
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
    parent::__construct(
      'Plugin/LeagueOauthLogin',
      $namespaces,
      $module_handler,
      'Drupal\league_oauth_login\LeagueOauthLoginInterface',
      'Drupal\league_oauth_login\Annotation\LeagueOauthLogin'
    );
    $this->alterInfo('league_oauth_login_info');
    $this->setCacheBackend($cache_backend, 'league_oauth_login_info');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as $id => $definition) {
      if (!isset($definition['login_enabled'])) {
        $definitions[$id]['login_enabled'] = TRUE;
      }
    }
    $this->setCachedDefinitions($definitions);
    return $definitions;
  }

}
