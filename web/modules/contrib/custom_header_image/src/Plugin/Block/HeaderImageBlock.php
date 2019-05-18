<?php

namespace Drupal\custom_header_image\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\PathMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Provides a 'HeaderImageBlock' block.
 *
 * @Block(
 *  id = "header_image_block",
 *  admin_label = @Translation("Header image block"),
 * )
 */
class HeaderImageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Path\AliasManager definition.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $pathAliasManager;
  /**
   * Drupal\Core\Path\PathMatcher definition.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;
  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;
  /**
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * Storage for the header_image entity type.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $headerImages;

  /**
   * Constructs a new HeaderImageBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\AliasManager $path_alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathMatcher $path_matcher
   *   The path macher.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\Core\Path\CurrentPathStack $path_current
   *   The current path stack.
   * @param \Drupal\Core\Entity\EntityStorageInterface $header_images
   *   The header images entity storage.
   *
   * @internal param \Drupal\Core\Config\Config $configured_images
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasManager $path_alias_manager, PathMatcher $path_matcher, RequestStack $request_stack, CurrentPathStack $path_current, EntityStorageInterface $header_images) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pathAliasManager = $path_alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->pathCurrent = $path_current;
    $this->headerImages = $header_images;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.alias_manager'),
      $container->get('path.matcher'),
      $container->get('request_stack'),
      $container->get('path.current'),
      $container->get('entity_type.manager')->getStorage('header_image')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $header_image = $this->getHeaderImageEntity();
    if ($header_image) {
      $result = AccessResult::allowed();
      $result->addCacheableDependency($header_image);
      // @todo Need to add a dependency to be cleared when any header image changes.
    }
    else {
      $result = AccessResult::forbidden();
      // @todo Need to add a dependency to be cleared when any header image changes.
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $header_image = $this->getHeaderImageEntity();
    if (!$header_image) {
      return [];
    }
    $file = $header_image->getHeaderImage();
    if ($file) {
      $images_build = [
        '#theme' => 'image_srcset',
        '#styles' => $header_image->getImageStyleIds(),
        '#uri' => $file->getFileUri(),
      ];

      if ($header_image->hasSizes()) {
        $images_build['#sizes'] = $header_image->getSizes();
      }

      if ($header_image->hasAltText()) {
        $images_build['#alt'] = $header_image->getAltText();
      }

      // Add the file entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $renderer = \Drupal::service('renderer');
      $renderer->addCacheableDependency($images_build, $file);
      $renderer->addCacheableDependency($images_build, $header_image);
      foreach ($header_image->getImageStyles() as $image_style) {
        $renderer->addCacheableDependency($images_build, $image_style);
      }

      // Return the render array as block content.
      return $images_build;
    }
    return [];
  }

  /**
   * Get the applicable header image entity.
   *
   * @return \Drupal\custom_header_image\Entity\HeaderImageInterface|NULL
   */
  protected function getHeaderImageEntity() {
    /** @var \Drupal\custom_header_image\Entity\HeaderImageInterface $header_image */
    foreach ($this->headerImages->loadMultiple() as $header_image) {
      if ($header_image->status() && $header_image->getPaths()) {
        $pages = Unicode::strtolower(implode("\n", $header_image->getPaths()));
        if (!$pages) {
          continue;
        }
        $request = $this->requestStack->getCurrentRequest();
        // Compare the lowercase path alias (if any) and internal path.
        $path = $this->pathCurrent->getPath($request);
        // Do not trim a trailing slash if that is the complete path.
        $path = $path === '/' ? $path : rtrim($path, '/');
        $path_alias = Unicode::strtolower($this->pathAliasManager->getAliasByPath($path));

        if ($this->pathMatcher->matchPath($path_alias, $pages) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pages))) {
          return $header_image;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    return $contexts;
  }

}
