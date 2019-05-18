<?php

/**
 * @file
 * Contains \Drupal\opcachectl\Controller\OpcacheReportController.
 */

namespace Drupal\opcachectl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Render\HtmlEscapedText;

/**
 * OPcache reports.
 */
class OpcacheReportController extends ControllerBase {

	/**
	 * Callback for the OPcache statistics page.
	 *
	 * @return array
	 *   An array suitable for drupal_render().
	 */
	public function viewStatistics() {
		return [
			'#theme' => 'opcache_stats',
			'#cache' => [
				'max-age' => 0,
			],
		];
	}


	/**
	 * Callback for the OPcache config dump page.
	 *
	 * @return array
	 *   An array suitable for drupal_render().
	 */
	public function viewConfig() {
		return [
			'#theme' => 'opcache_config',
			'#cache' => [
				'max-age' => 0,
			],
		];
	}


}
