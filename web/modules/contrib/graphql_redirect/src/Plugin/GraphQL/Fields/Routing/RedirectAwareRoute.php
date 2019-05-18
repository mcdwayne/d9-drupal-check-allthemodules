<?php

namespace Drupal\graphql_redirect\Plugin\GraphQL\Fields\Routing;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\graphql\GraphQL\Cache\CacheableValue;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql_core\Plugin\GraphQL\Fields\Routing\Route;
use Drupal\language\LanguageNegotiator;
use Drupal\redirect\RedirectRepository;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Retrieve a route object based on a path.
 *
 * @GraphQLField(
 *   id = "redirect_aware_url_route",
 *   secure = true,
 *   name = "route",
 *   description = @Translation("Loads a route by its path."),
 *   type = "Url",
 *   arguments = {
 *     "path" = "String!"
 *   },
 *   weight = 1
 * )
 */
class RedirectAwareRoute extends Route {
  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The redirect entity repository.
   *
   * @var \Drupal\Redirect\RedirectRepository
   */
  protected $redirectRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.validator'),
      $container->has('language_negotiator') ? $container->get('language_negotiator') : NULL,
      $container->get('language_manager'),
      $container->get('redirect.repository')
    );
  }

  /**
   * Route constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator service.
   * @param \Drupal\language\LanguageNegotiator|null $languageNegotiator
   *   The language negotiator.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language manager service.
   * @param \Drupal\Redirect\RedirectRepository $redirectRepository
   *   The path validator service.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    PathValidatorInterface $pathValidator,
    $languageNegotiator,
    LanguageManager $languageManager,
    RedirectRepository $redirectRepository
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition, $pathValidator, $languageNegotiator, $languageManager);
    $this->languageManager = $languageManager;
    $this->redirectRepository = $redirectRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    // Remove language prefix from path to find matching redirects.
    $path = trim($args['path'],'/');
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $langcode = $context->getContext('language', $info, $default_langcode);
    $path_args = explode('/', $path);
    $prefix = array_shift($path_args);
    if ($prefix == $langcode) {
      $path = implode('/', $path_args);
    }

    $redirect_entity = $this->redirectRepository->findMatchingRedirect($path, [], $langcode);
    if ($redirect_entity) {
      $url = $redirect_entity->getRedirectUrl();
      $url->setOption('language', $redirect_entity->language());
      $context->addCacheableDependency($redirect_entity);
      yield $url;
    }
    else {
      foreach (parent::resolveValues($value, $args, $context, $info) as $item) {
        yield $item;
      }
    }
  }

}
