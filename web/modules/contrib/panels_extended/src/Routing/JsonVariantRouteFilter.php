<?php

namespace Drupal\panels_extended\Routing;

use Drupal\page_manager\PageVariantInterface;
use Drupal\page_manager\Routing\VariantRouteFilter;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels_extended\Controller\PanelsJsonController;
use Drupal\panels_extended\Plugin\DisplayBuilder\JsonDisplayBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters variant routes for JSON requests.
 *
 * Changes the view controller when requesting JSON.
 */
class JsonVariantRouteFilter extends VariantRouteFilter {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    if (_panels_extended_is_json_requested()) {
      if (($pageVariant = $request->get('page_manager_page_variant')) instanceof PageVariantInterface) {
        $variant = $pageVariant->getVariantPlugin();
        if ($variant instanceof PanelsDisplayVariant && $variant->getBuilder() instanceof JsonDisplayBuilder) {
          foreach ($collection as $route) {
            $route->setDefault('_controller', PanelsJsonController::class . '::view');
          }
        }
      }
    }
    return $collection;
  }

}
