<?php

namespace Drupal\purest_content\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a resource to get node and taxonomy term entities by path alias.
 *
 * @RestResource(
 *   id = "purest_content_resource",
 *   label = @Translation("Purest Content Resource"),
 *   uri_paths = {
 *     "canonical" = "/purest/content"
 *   }
 * )
 */
class ContentResource extends ResourceBase {

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * AliasStorageInterface.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorageInterface;

  /**
   * LanguageManager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The language.
   *
   * @var int
   */
  protected $language;

  /**
   * The request alias.
   *
   * @var string
   */
  protected $requestAlias;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * AliasManager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ProductResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage_interface
   *   Alias storage interface.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\AliasManager $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    Request $current_request,
    AliasStorageInterface $alias_storage_interface,
    LanguageManager $language_manager,
    AliasManager $alias_manager,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentRequest = $current_request;
    $this->aliasStorageInterface = $alias_storage_interface;
    $this->languageManager = $language_manager;
    $this->language = $this->languageManager->getCurrentLanguage()->getId();
    $this->requestAlias = $this->currentRequest->query->get('alias');
    $this->aliasManager = $alias_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.alias_storage'),
      $container->get('language_manager'),
      $container->get('path.alias_manager'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   A resource response.
   */
  public function get() {
    $account = \Drupal::currentUser();
    $cache_metadata = (new CacheableMetadata())->addCacheContexts([
      'url.query_args:alias',
    ]);
    $alias_language = $this->language;
    $exists = FALSE;
    $purest_content_config = $this->configFactory->get('purest_content.settings');

    if ($this->languageManager->isMultilingual()) {
      // Get language negotiation methods, sort by weight
      // and try in order ignoring 'language-user-admin'.
      $language_negotiator = \Drupal::service('language_negotiator');
      $negotiation_methods = $language_negotiator->getNegotiationMethods();
      uasort($negotiation_methods, '\Drupal\Component\Utility\SortArray::sortByWeightProperty');
      $negotiation_methods = array_reverse($negotiation_methods);

      foreach ($negotiation_methods as $key => $method) {
        if ($method['id'] === 'language-user-admin' ||
              !$language_negotiator->isNegotiationMethodEnabled($method['id'])) {
          continue;
        }

        $method_instance = $language_negotiator->getNegotiationMethodInstance($method['id']);

        switch ($method['id']) {
          case 'language-user':
            $user = $this->currentUser->getAccount();

            if ($user) {
              $alias_language = $user->getPreferredLangcode();
            }
            break;

          case 'language-url':
            $config = \Drupal::service('config.factory')->get('language.negotiation');
            $url_source = $config->get('url.source');

            if ($url_source === 'path_prefix') {
              // Find any valid langauge prefix in requestAlias and set
              // current language with it then remove from requestAlias
              // if it is a valid language code.
              $available_languages = $this->languageManager->getLanguages();
              $language_prefixes = array_keys($available_languages);

              if (!empty($this->requestAlias)) {
                $path_parts = explode('/', trim($this->requestAlias, '/'));

                if (!empty($path_parts) && !empty($language_prefixes)) {
                  if (in_array($path_parts[0], $language_prefixes)) {
                    $alias_language = $path_parts[0];
                    array_shift($path_parts);
                    $this->requestAlias = '/' . implode('/', $path_parts);
                  }
                }
              }
            }
            else {
              if ($origin = $this->currentRequest->headers->get('origin')) {
                $domains = $config->get('url.domains');
                $origin = explode('//', $origin);
                $origin = end($origin);

                foreach ($domains as $key => $domain) {
                  if ($origin === $domain) {
                    $alias_language = $key;
                  }
                }
              }
            }
            break;

          case 'language-selected':

            break;
        }
      }
    }

    if ($this->aliasStorageInterface->aliasExists($this->requestAlias, $alias_language)) {
      $exists = TRUE;
    }

    if ($exists) {
      $language = $this->languageManager->getLanguage($alias_language);
      $path = $this->aliasManager->getPathByAlias($this->requestAlias, $alias_language);
      $url = Url::fromUri('internal:' . $path, ['language' => $language]);
      $params = $url->getRouteParameters();
      $entity_type = key($params);
      $this->entityStorage = $this->entityTypeManager->getStorage($entity_type);
      $entity = $this->entityStorage->load($params[$entity_type]);
      $entity = $entity->getTranslation($alias_language);
      $check = $entity->access('view', $account);

      if ($check) {
        $response = new ResourceResponse($entity, 200);
        $response->addCacheableDependency($entity);
        $response->addCacheableDependency($cache_metadata);
        $response->addCacheableDependency($purest_content_config);

        return $response;
      }
      else {
        $config = $this->configFactory->get('system.site');
        $page_403 = $config->get('system.site')->get('page.403');

        if ($page_403) {
          $this->entityStorage = $this->entityTypeManager->getStorage('node');
          $entity = $this->entityStorage->load(basename($page_403));

          if ($entity) {
            $response = new ResourceResponse($entity, 403);
            $response->addCacheableDependency($config);
            $response->addCacheableDependency($purest_content_config);
            $response->addCacheableDependency($cache_metadata);
            $response->addCacheableDependency($entity);
            return $response;
          }
        }
        else {
          $response = new ResourceResponse([
            'error' => t('Active user does not have permission to view this content.'),
          ], 403);
        }
      }
    }

    $config = $this->configFactory->get('system.site');
    $page_404 = $config->get('page.404');

    if ($page_404) {
      $this->entityStorage = $this->entityTypeManager->getStorage('node');
      $entity = $this->entityStorage->load(basename($page_404));

      if ($entity) {
        $response = new ResourceResponse($entity, 404);
        $response->addCacheableDependency($config);
        $response->addCacheableDependency($purest_content_config);
        $response->addCacheableDependency($cache_metadata);
        $response->addCacheableDependency($entity);
        return $response;
      }
    }

    $response = new ResourceResponse([
      'error' => t('No entity with the provided alias "@alias" exists.', [
        '@alias' => $this->requestAlias,
      ]),
    ], 404);

    $response->addCacheableDependency($config);
    $response->addCacheableDependency($purest_content_config);
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

}
