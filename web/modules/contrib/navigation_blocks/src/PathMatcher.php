<?php

namespace Drupal\navigation_blocks;

use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface as CorePathMatcherInterface;
use Drupal\Core\Url;

/**
 * Path matcher for back buttons.
 *
 * @package Drupal\navigation_blocks
 */
class PathMatcher implements PathMatcherInterface {

  /**
   * Alias Manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Current Path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Path Matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new path matcher.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   The current path stack.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The core path matcher.
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   *   The alias manager.
   */
  public function __construct(CurrentPathStack $currentPath, CorePathMatcherInterface $pathMatcher, AliasManagerInterface $aliasManager) {
    $this->currentPath = $currentPath;
    $this->pathMatcher = $pathMatcher;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritdoc}
   */
  public function matchPath(string $path, string $preferredPaths): bool {
    $path = $path === '/' ? $path : \rtrim($path, '/');
    $path = $this->aliasManager->getPathByAlias($path);
    $path_alias = \mb_strtolower($this->aliasManager->getAliasByPath($path));
    return $this->pathMatcher->matchPath($path_alias, $preferredPaths) || (($path !== $path_alias) && $this->pathMatcher->matchPath($path, $preferredPaths));
  }

  /**
   * {@inheritdoc}
   */
  public function validateCurrentPath(Url $url): bool {
    return !($this->aliasManager->getAliasByPath($this->currentPath->getPath()) === '/' . $url->getInternalPath());
  }

}
