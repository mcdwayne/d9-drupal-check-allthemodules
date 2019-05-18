<?php

namespace Drupal\micro_menu\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes the outbound path by resolving it to the site entity menu route.
 */
class RouteProcessor implements OutboundRouteProcessorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a RouteProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   */
  function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    $routes_to_check =[
      'entity.menu_link_content.canonical',
      'entity.menu_link_content.edit_form',
      'entity.menu_link_content.delete_form',
      'entity.menu.add_link_form',
      'entity.menu.edit_form',
      'entity.menu_link_content.content_translation_overview',
      'entity.menu_link_content.content_translation_add',
    ];

    if (strpos('entity.menu_link_content', $route_name) !== FALSE) {
      if ($route_name) {

      }
    }

    if (in_array($route_name, $routes_to_check)) {
      $request = $this->requestStack->getCurrentRequest();
      /** @var \Drupal\micro_site\Entity\SiteInterface $site */
      $site = $request->get('site');
      /** @var \Drupal\system\MenuInterface $menu */
      $menu = $request->get('menu');

      // @TODO test if the active site is retrieved when we are on the site url.
      // Take over URI construction for menu link.
      if ($site instanceof SiteInterface && $menu instanceof MenuInterface) {
        switch ($route_name) {
          case 'entity.menu_link_content.canonical':
          case 'entity.menu_link_content.edit_form':
            $route->setPath('/site/{site}/menu/{menu}/item/{menu_link_content}/edit');
            break;

          case 'entity.menu_link_content.delete_form':
            $route->setPath('/site/{site}/menu/{menu}/item/{menu_link_content}/delete');
            break;

          case 'entity.menu.add_link_form':
            $route->setPath('/site/{site}/menu/{menu}/add');
            break;

          case 'entity.menu.edit_form':
            $route->setPath('/site/{site}/menu/{menu}');
            break;

          case 'entity.menu_link_content.content_translation_overview':
            $route->setPath('/site/{site}/menu/{menu}/item/{menu_link_content}/edit/translations');
            break;

          case 'entity.menu_link_content.content_translation_add':
            $route->setPath('/site/{site}/menu/{menu}/item/{menu_link_content}/edit/add/{source}/{target}');
            break;
        }

        $route_parameters = $route->getOption('parameters');
        $route_parameters += $this->getSiteMenuParameters();
        $route->setOption('parameters', $route_parameters);

        // Provide the parameters to the route altered.
        $parameters['site'] = $site->id();
        $parameters['menu'] = $menu->id();
      }
    }

  }

  /**
   * Helper function to get default parameters for site menu route.
   *
   * @return array $options
   *   The default options for a menu in a site context route.
   */
  protected function getSiteMenuParameters() {
    $parameters = [];
    $parameters['site'] = [
      'type' => 'entity:site',
      'with_config_overrides' => TRUE,
    ];
    $parameters['menu'] = [
      'type' => 'entity:menu',
      'with_config_overrides' => TRUE,
    ];
    return $parameters;
  }

}
