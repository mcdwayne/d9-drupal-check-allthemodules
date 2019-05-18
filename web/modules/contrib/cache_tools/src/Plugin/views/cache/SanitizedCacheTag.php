<?php

namespace Drupal\cache_tools\Plugin\views\cache;

use Drupal\cache_tools\Service\CacheSanitizer;
use Drupal\node\Plugin\views\argument\Type;
use Drupal\views\Plugin\views\cache\Tag;
use Drupal\views\Plugin\views\filter\Bundle;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple caching of query results for Views displays.
 *
 * Module is auto invalidating content tagged by such a cache tag.
 *
 * @ingroup views_cache_plugins
 *
 * @ViewsCache(
 *   id = "cache_tools_sanitized_cache_tag",
 *   title = @Translation("Sanitized cache tag"),
 *   help = @Translation("Tag based cache with sanitized tags")
 * )
 */
class SanitizedCacheTag extends Tag {

  /**
   * Cache sanitizer.
   *
   * @var \Drupal\cache_tools\Service\CacheSanitizer
   */
  protected $cacheSanitizer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, CacheSanitizer $cacheSanitizer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cacheSanitizer = $cacheSanitizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache_tools.cache.sanitizer')
    );
  }

  /**
   * Extracts entity type filters and create 'entitytype_entitybundle_pub' tags.
   *
   * TODO: Make this work with custom conditions (e.g. NOT, or grouped one).
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   *
   * @return array
   *   Published cache tags 'entitytype_entitybundle_pub'.
   */
  protected function extractPublishedTagsFromView(ViewExecutable $view) {
    $tags = [];
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase $currentDisplay */
    $currentDisplay = $view->getDisplay();
    $lookupHandlers = [
      'filter' => Bundle::class,
      'argument' => Type::class,
    ];
    foreach ($lookupHandlers as $handlerName => $handlerClass) {
      $handlers = $currentDisplay->getHandlers($handlerName);
      foreach ($handlers as $handler) {
        if ($handler instanceof $handlerClass) {
          /** @var \Drupal\views\Plugin\views\HandlerBase $handler */
          foreach ($handler->value as $bundle) {
            try {
              $entityType = $handler->getEntityType();
            }
            catch (\Exception $e) {
              continue;
            }
            $tags[] = $this->createTag($entityType, $bundle);
          }
        }
      }
    }
    // If no tag was found mark with general entity type tag.
    if (empty($tags)) {
      return [$this->cacheSanitizer->getPublishedEntityTypeCacheTag($view->getBaseEntityType())];
    }
    return $tags;
  }

  /**
   * Create cache tag based on entity type and its bundle.
   *
   * @param string $entityType
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   *
   * @return string
   *   Cache tag.
   */
  protected function createTag($entityType, $bundle) {
    return $entityType . '_' . $bundle . '_pub';
  }

  /**
   * Attach `entitytype_entitybundle_pub` cache tags based on view result.
   *
   * @return string[]
   *   An array of cache tags based on the current view.
   */
  public function getCacheTags() {
    // Get the original cache tags for the view.
    $originalCacheTags = parent::getCacheTags();
    // Extract the entity type related cache tags.
    $extractPublishedTags = $this->extractPublishedTagsFromView($this->view);
    // Merge the cache tags.
    $cacheTags = array_merge($originalCacheTags, $extractPublishedTags);
    // Sanitize the cache tags.
    return $this->cacheSanitizer->sanitizeCacheableTags($cacheTags);
  }

}
