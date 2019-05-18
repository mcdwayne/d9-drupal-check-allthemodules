<?php
/**
 * @file
 * Contains \Drupal\direct_clear_cache\Controller\direct_clear_cache.
 */
 
namespace Drupal\direct_clear_cache\Controller;
 
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
 
class direct_clear_cache extends ControllerBase {
	public function content() {
		$previousUrl = \Drupal::request()->server->get('HTTP_REFERER');
		drupal_flush_all_caches();
		drupal_set_message('Cache flushed successfully.');
		return new RedirectResponse($previousUrl);
		
	}
}