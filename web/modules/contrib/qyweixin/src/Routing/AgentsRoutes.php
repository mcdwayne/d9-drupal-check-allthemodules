<?php

namespace Drupal\qyweixin\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides dynamic routes for qyweixin.
 */
class AgentsRoutes {

	/**
	* Returns an array of route objects.
	*
	* @return \Symfony\Component\Routing\Route[]
	*   An array of route objects.
	*/
	public function routes() {
		$routes = new RouteCollection();
		$agents=\Drupal::config('qyweixin.general')->get('agent');
		$prefix=sprintf('qyweixin.%s',\Drupal::config('qyweixin.general')->get('corpid'));
		if(empty($agents)) return;
		foreach($agents as $agentId => $settings) {
			if($settings['enabled']) {
				$route=new Route(
					'/qyweixin/'.$settings['entryclass'],
					array(
						'_controller' => '\Drupal\qyweixin\Controller\QyWeixinController::defaultResponse',
					),
					array(
						'_access' => 'TRUE'
					)
				);
				$routes->add(sprintf('%s.%s.%s', $prefix, $settings['entryclass'], $agentId), $route);
			}
		}
		return $routes;
	}
}
