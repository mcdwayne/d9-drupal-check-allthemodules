<?php

namespace Drupal\hidden_tab\Routing;

use Drupal\hidden_tab\Controller\XPageRenderController;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DynamicRoute. Adds route of Hidden Tabs (Secret Uris) to route system.
 *
 * @package Drupal\hidden_tab\Routing
 */
class DynamicRouting {

  const CURRENTLY_SUPPORTING_ENTITY_TYPE = 'node';

  /**
   * Creates dynamic route for all the Hidden Tab Page entities.
   *
   * Disabled pages ($page->isEnabled()) are skipped.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   All the routes created by this class.
   */
  public function routes(): RouteCollection {
    $op['parameters'][self::CURRENTLY_SUPPORTING_ENTITY_TYPE]['type']
      = 'entity:' . self::CURRENTLY_SUPPORTING_ENTITY_TYPE;
    $op['no_cache'] = 'TRUE';

    $base_path = '/' . self::CURRENTLY_SUPPORTING_ENTITY_TYPE . '/{' . self::CURRENTLY_SUPPORTING_ENTITY_TYPE . '}/';
    $ctl = XPageRenderController::class . '::display';

    $rc = new RouteCollection();
    foreach (HiddenTabEntityHelper::instance()->pages() as $page) {
      if (!$page->status()) {
        continue;
      }

      $tab_uri = $base_path . $page->tabUri();
      $tab_perm = $page->tabViewPermission()
        ? ['_permission' => $page->tabViewPermission()]
        : ['_access' => 'TRUE'];
      $defaults = [
        '_controller' => $ctl,
        '_title' => $page->label(),
      ];

      $route = new Route($tab_uri, $defaults, $tab_perm, $op);
      $rc->add('hidden_tab.tab_' . $page->id(), $route);

      if (!$page->secretUri()) {
        continue;
      }

      $sec_uri = $base_path . $page->secretUri();
      $sec_perm = $page->secretUriViewPermission()
        ? ['_permission' => $page->secretUriViewPermission()]
        : ['_access' => 'TRUE'];
      $route = new Route($sec_uri, $defaults, $sec_perm, $op);
      $rc->add('hidden_tab.uri_' . $page->id(), $route);
    }

    return $rc;
  }

}
