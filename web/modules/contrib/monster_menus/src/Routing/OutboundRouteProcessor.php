<?php

namespace Drupal\monster_menus\Routing;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides a route processor to replace unresolved {mm_tree} path elements with
 * the current page's MMTID.
 */
class OutboundRouteProcessor implements OutboundRouteProcessorInterface  {

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    $link_url = $route->getPath();
    if (!strncmp($link_url, '/mm/{mm_tree}', 13) && !isset($parameters['mm_tree'])) {
      if (in_array($route_name, ['entity.node.edit_form', 'node.add', 'entity.node.preview']) && ($mmtid = static::getMmtidFromQuery())) {
        $parameters['mm_tree'] = $mmtid;
      }
      else {
        $current_page = mm_active_menu_item();
        $subst = [];
        foreach ($parameters as $name => $value) {
          $subst['{' . $name . '}'] = $value;
        }
        $without_prefix = substr($link_url, 13);
        $expanded = str_replace(array_keys($subst), $subst, $without_prefix);
        $link_page = mm_active_menu_item($expanded);

        if ($mmtid = isset($current_page->mmtid) && !is_null($current_page->nid) ? $current_page->mmtid : (isset($link_page->mmtid) ? $link_page->mmtid : (isset($current_page->mmtid) ? $current_page->mmtid : NULL))) {
          $parameters['mm_tree'] = $mmtid;
        }
        else {
          $route->setPath($without_prefix);
        }
      }
    }

    if (isset($parameters['mm_tree']) && $bubbleable_metadata) {
      $bubbleable_metadata->addCacheTags(['mm_tree:' . $parameters['mm_tree']]);
    }
  }

  public static function getMmtidFromQuery() {
    if ($mm_tree = \Drupal::request()->attributes->get('mm_tree')) {
      return is_numeric($mm_tree) ? $mm_tree : $mm_tree->id();
    }
    return 0;
  }

}
