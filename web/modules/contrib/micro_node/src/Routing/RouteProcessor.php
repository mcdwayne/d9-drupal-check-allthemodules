<?php

namespace Drupal\micro_node\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\micro_site\SiteNegotiatorInterface;

/**
 * Processes the outbound path by resolving it to the site entity node route.
 *
 * @TODO not used by now. Waiting to be removed if we not support editing node without
 * an active site (from the hostname).
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
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a RouteProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack, SiteNegotiatorInterface $site_negotiator) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    $routes_to_check =[
      'entity.node.canonical',
      'entity.node.edit_form',
      'entity.node.delete_form',
    ];

    if (in_array($route_name, $routes_to_check) && !empty($parameters['node'])) {
      $node = $this->entityTypeManager->getStorage('node')->load($parameters['node']);
      $site = ($node) ? $node->get('site_id')->referencedEntities() : [];
      $site = reset($site);
      // Take over URI construction for node attached to site.
      if ($site instanceof SiteInterface) {
        switch ($route_name) {
          case 'entity.node.canonical':
            $route->setPath('/site/{site}/node/{node}');
            break;

          case 'entity.node.edit_form':
            $route->setPath('/site/{site}/node/{node}/edit');
            break;

          case 'entity.node.delete_form':
            $route->setPath('/site/{site}/node/{node}/edit');
            break;
        }

        $route_parameters = $route->getOption('parameters');
        $route_parameters += $this->getSiteParameters();
        $route->setOption('parameters', $route_parameters);

        // Provide the parameters to the route altered.
        $parameters['site'] = $site->id();

        $route->addRequirements(['_site_node_access' => 'TRUE']);
      }
      if ($route) {

      }

    }



  }

  /**
   * Helper function to get default parameters for route node on site.
   */
  protected function getSiteParameters() {
    $parameters = [];
    $parameters['site'] = [
      'type' => 'entity:site',
      'with_config_overrides' => TRUE,
    ];
    return $parameters;
  }

}
