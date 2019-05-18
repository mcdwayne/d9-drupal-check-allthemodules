<?php

namespace Drupal\monster_menus\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;

/**
 * Provides a custom breadcrumb builder that knows about the MM Tree structure.
 */
class MMBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return !is_null($route_match->getParameter('mm_tree'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    mm_parse_args($mmtids, $oarg_list);
    if (!$mmtids) {
      return $breadcrumb;
    }

    if (isset($mmtids[0]) && $mmtids[0] == mm_home_mmtid()) {
      array_shift($mmtids);
    }

    $base = mm_content_get(mm_home_mmtid());
    $bread = [mm_home_mmtid() => Link::createFromRoute(mm_content_get_name($base), '<front>')];
    foreach ($mmtids as $mmtid) {
      if (!($tree = mm_content_get($mmtid, Constants::MM_GET_FLAGS))) {
        break;
      }

      if ($mmtid == $mmtids[count($mmtids) - 1] || !isset($tree->flags['no_breadcrumb'])) {
        $bread[$mmtid] = Link::fromTextAndUrl(mm_content_get_name($tree), mm_content_get_mmtid_url($mmtid));
      }

      if (!mm_content_user_can($mmtid, Constants::MM_PERMS_READ)) {
        break;
      }
    }

    $breadcrumb->setLinks($bread);
    foreach (MMTree::loadMultiple(array_keys($bread)) as $mm_tree) {
      $breadcrumb->addCacheableDependency($mm_tree);
    }
    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }

}
