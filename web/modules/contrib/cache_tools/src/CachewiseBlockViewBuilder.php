<?php

namespace Drupal\cache_tools;

use Drupal\block\BlockViewBuilder;
use Drupal\cache_tools\Service\CacheSanitizer;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides cache-wise block view builder.
 */
class CachewiseBlockViewBuilder extends BlockViewBuilder {

  /**
   * The cache sanitizer.
   *
   * @var \Drupal\cache_tools\Service\CacheSanitizer
   */
  protected $cacheSanitizer;

  /**
   * Constructs a new CachewiseBlockViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\cache_tools\Service\CacheSanitizer $cacheSanitizer
   *   The cache sanitizer.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, CacheSanitizer $cacheSanitizer) {
    parent::__construct($entity_type, $entity_manager, $language_manager, $module_handler);
    $this->cacheSanitizer = $cacheSanitizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('cache_tools.cache.sanitizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = parent::viewMultiple($entities, $view_mode, $langcode);
    foreach ($entities as $block) {
      $this->cacheSanitizer->sanitize($block, $build[$block->id()]);
    }
    return $build;
  }

  /**
   * Sanitize the build also during prerendering.
   *
   * Helps to prevent views block to place undesired cacheable metadata.
   *
   * {@inheritdoc}
   */
  public static function preRender($build) {
    static $cacheSanitizer;
    if (!isset($cacheSanitizer)) {
      /** @var \Drupal\cache_tools\Service\CacheSanitizer $cacheSanitizer */
      $cacheSanitizer = \Drupal::service('cache_tools.cache.sanitizer');
    }
    $block = $build['#block'];
    $build = parent::preRender($build);
    $cacheSanitizer->sanitize($block, $build);
    return $build;
  }

}
