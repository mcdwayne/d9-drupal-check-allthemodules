<?php

/**
 * @file
 * Contains \Drupal\qyweixin\QyWeixinAgentManager.
 */

namespace Drupal\qyweixin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages qyweixin agent plugins.
 *
 */
class QyWeixinAgentManager extends DefaultPluginManager {

	/**
	* Constructs a new QyWeixinAgentManager.
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
		parent::__construct('Plugin/QyWeixinAgent', $namespaces, $module_handler, 'Drupal\qyweixin\AgentInterface', 'Drupal\qyweixin\Annotation\QyWeixinAgent');

		$this->alterInfo('qyweixin_agent_info');
		$this->setCacheBackend($cache_backend, 'qyweixin_plugins');
	}
	
}
