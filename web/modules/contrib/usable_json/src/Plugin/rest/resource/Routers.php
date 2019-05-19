<?php

namespace Drupal\usable_json\Plugin\rest\resource;

use Drupal;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Path\AliasStorage;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "routers",
 *   label = @Translation("routers"),
 *   bundle = "Routers",
 *   uri_paths = {
 *     "canonical" = "/routers",
 *   }
 * )
 */
class Routers extends ResourceBase {

  protected $aliasStorage;
  protected $controllerResolver;
  protected $allowedRouteNames = ['entity.node.canonical'];

  /**
   * Routers constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Path\AliasStorage $aliasStorage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, \Psr\Log\LoggerInterface $logger, AliasStorage $aliasStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->controllerResolver = Drupal::service('controller_resolver');
    $this->aliasStorage = $aliasStorage;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('path.alias_storage')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    $metadata = new CacheableMetadata();
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $moduleHandler = \Drupal::service('module_handler');

    // TODO: fix language here!
    $aliases = $this->aliasStorage->preloadPathAlias(NULL, $language);
    $routings = [];

    $site_frontpage = \Drupal::config('system.site')->get('page.front');
    $home_url_object = Drupal::service('path.validator')
      ->getUrlIfValid($site_frontpage . '?_format=html');

    if ($home_url_object) {
      $component = $this->getComponentNameByRouterName($home_url_object->getRouteName());
      $routings[] = array(
        'path' => '/',
        'component' => $component,
        'data' => $home_url_object->getRouteParameters(),
        'resolve' => array(
          $component => $component . 'Resolver',
        ),
      );
    }
    else {
      $routings[] = array(
        'path' => '/',
        'component' => 'Home',
      );
    }

    foreach ($aliases as $internUrl => $aliasUrl) {
      // Getting the right controller name..
      // because other wise we get for example a json controller.
      /* @var $url_object \Drupal\Core\Url */
      $url_object = Drupal::service('path.validator')
        ->getUrlIfValid($aliasUrl . '?_format=html');
      if (!$url_object) {
        continue;
      }

      if (!in_array($url_object->getRouteName(), $this->allowedRouteNames)) {
        continue;
      }

      $component = $this->getComponentNameByRouterName($url_object->getRouteName());
      $routings[] = [
        'path' => $aliasUrl,
        'component' => $component,
        'data' => $url_object->getRouteParameters(),
        'resolve' => [
          $component => $component . 'Resolver',
        ],
      ];

    }

    if ($moduleHandler->moduleExists('redirect')) {
      $redirects = \Drupal::service('redirect.repository')->loadMultiple();
      /* @var \Drupal\redirect\Entity\Redirect $redirect */
      foreach ($redirects as $redirect) {
        $metadata->addCacheableDependency($redirect);
        $url = $redirect->getRedirectUrl()->toString(TRUE);
        $routings[] = [
          'path' => $redirect->getSourceUrl(),
          'component' => 'Redirect',
          'data' => [
            'redirectUrl' => $url->getGeneratedUrl(),
            'redirectStatus' => $redirect->getStatusCode(),
          ],
        ];
      }
    }

    $routings[] = [
      'path' => '*',
      'component' => 'NotFound',
    ];

    $response = new ResourceResponse($routings);
    $rMetadata = $response->getCacheableMetadata();
    $rMetadata->addCacheTags(['route_match', 'routes']);
    $rMetadata->merge($metadata);

    return $response;
  }

  /**
   * Strip route name to component name.
   *
   * @param string $name
   *   Router name.
   *
   * @return mixed|string
   *   return component name.
   */
  private function getComponentNameByRouterName($name) {
    $name = str_replace('.canonical', '', $name);
    $name = ucwords($name, ".");
    $name = str_replace('.', '', $name);

    return $name;
  }

}
