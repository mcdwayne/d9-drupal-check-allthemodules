<?php

namespace Drupal\micro_taxonomy\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\system\MenuInterface;
use Drupal\taxonomy\VocabularyInterface;
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
      'entity.taxonomy_term.add_form',
    ];

    if (in_array($route_name, $routes_to_check)) {
      $request = $this->requestStack->getCurrentRequest();
      /** @var \Drupal\micro_site\Entity\SiteInterface $site */
      $site = $request->get('site');
      /** @var \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary */
      $taxonomy_vocabulary = $request->get('taxonomy_vocabulary');

      // @TODO test if the active site is retrieved when we are on the site url.
      // Take over URI construction for vocabulary related link.
      if ($site instanceof SiteInterface && $taxonomy_vocabulary instanceof VocabularyInterface) {
        switch ($route_name) {
          case 'entity.taxonomy_term.add_form':
            $route->setPath('/site/{site}/taxonomy/{taxonomy_vocabulary}/add');
            break;
        }

        $route_parameters = $route->getOption('parameters');
        $route_parameters += $this->getSiteTaxonomyParameters();
        $route->setOption('parameters', $route_parameters);

        // Provide the parameters to the route altered.
        $parameters['site'] = $site->id();
        $parameters['taxonomy_vocabulary'] = $taxonomy_vocabulary->id();
      }
    }

  }

  /**
   * Helper function to get default parameters for site taxonomy vocabulary route.
   *
   * @return array $options
   *   The default options for a vocabulary in a site context route.
   */
  protected function getSiteTaxonomyParameters() {
    $parameters = [];
    $parameters['site'] = [
      'type' => 'entity:site',
      'with_config_overrides' => TRUE,
    ];
    $parameters['taxonomy_vocabulary'] = [
      'type' => 'entity:taxonomy_vocabulary',
      'with_config_overrides' => TRUE,
    ];
    return $parameters;
  }

}
