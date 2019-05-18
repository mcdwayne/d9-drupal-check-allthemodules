<?php

namespace Drupal\services_path\Plugin\ServiceDefinition;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\services\ServiceDefinitionBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @ServiceDefinition(
 *   id = "path_service_definition",
 *   title = @Translation("Path Service Definition"),
 *   description = @Translation("Provide a 'path' resource to expose information about a Drupal path."),
 *   translatable = true,
 *   methods = {
 *     "GET"
 *   },
 *   category = @Translation("Path"),
 *   path = "path"
 * )
 */
class PathServiceDefinition extends ServiceDefinitionBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;




  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('router'),
      $container->get('language_manager'),
      $container->get('metatag.manager'),
      $container->get('config.factory')
    );
  }


  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              RouterInterface $router,
                              LanguageManagerInterface $languageManager,
                              MetatagManagerInterface $metatagManager,
                              ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->router = $router;
    $this->languageManager = $languageManager;
    $this->metatagManager = $metatagManager;
    $this->configFactory = $configFactory;
  }


  /**
   * Testing hello world style request.
   */
  public function processRequest(Request $request, RouteMatchInterface $route_match, SerializerInterface $serializer) {
    if (!$request->query->has('path')) {
      throw new HttpException(404);
    }

    $provided_path = $request->query->get('path');
    $provided_path_request = Request::create($provided_path);
    \Drupal::requestStack()->push($provided_path_request);

    $this->languageManager->reset();
    $this->languageManager->getCurrentLanguage(Language::TYPE_URL);
    $this->languageManager->getCurrentLanguage(Language::TYPE_INTERFACE);
    $language = $this->languageManager->getCurrentLanguage(Language::TYPE_CONTENT);
    $langcode = $language->getId();



    try {
      $route = $this->router->matchRequest($provided_path_request);
    }catch(ResourceNotFoundException $e){
      throw new HttpException(404, 'Path not found', $e);
    }

    $provided_path_request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, $route['_route_object']);
    $routeMatch = RouteMatch::createFromRequest($provided_path_request);
    $pathMatch = new PathMatcher($this->configFactory, $routeMatch);
    $frontPage = $pathMatch->isFrontPage();

    $result = ['language' => $langcode, 'frontPage' => $frontPage];

    switch ($route['_route']) {
      case 'entity.node.canonical':
      case 'entity.taxonomy_term.canonical':
      case 'entity.webform.canonical':

        $entity_key = preg_replace('/^entity\.([a-zA-Z0-9_]+)\.canonical$/', '\1', $route['_route']);
        $this->entity = $route[$entity_key];

        if($this->entity instanceof ContentEntityInterface) {
          /**
           * @var \Drupal\Core\Render\RendererInterface
           */
          $renderer = \Drupal::getContainer()->get('renderer');
          $context = new RenderContext();
          $metatags = $renderer->executeInRenderContext($context, function () {
            $metatags = $this->metatagManager->tagsFromEntityWithDefaults($this->entity);
            $metatags = $this->metatagManager->generateRawElements($metatags, $this->entity);
            // Now call the actual controller, just like HttpKernel does.
            return array_map(function ($item) {
              return [
                'tag' => $item['#tag'],
                'attributes' => $item['#attributes'],
              ];
            }, $metatags);
          });
        }

        $result['entity'] = [
          'type' => $this->entity->getEntityTypeId(),
          'id' => $this->entity->id(),
          'uuid' => $this->entity->uuid(),
          'bundle' => $this->entity->bundle(),
        ];
        $result['metatags'] = $metatags;
        break;
    }

    \Drupal::requestStack()->pop();

    return $result;
  }

  public function getCacheContexts() {

    $contexts = ['languages:language_content', 'url.query_args:path'];

    if ($this->entity instanceof CacheableDependencyInterface) {
      $contexts = Cache::mergeContexts($contexts, $this->entity->getCacheContexts());
    }
    return $contexts;

  }


  public function getCacheTags() {
    $tags= [];
    // Applied contexts can affect the cache tags when this plugin is
    // involved in caching, collect and return them.
    if ($this->entity instanceof CacheableDependencyInterface) {
      $tags = [$this->entity->getEntityTypeId().':'.$this->entity->id()];
      $tags = Cache::mergeTags($tags, $this->entity->getCacheTags());
    }
    /* @var $context \Drupal\Core\Cache\CacheableDependencyInterface */
    return $tags;
  }

}
