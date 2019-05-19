<?php

namespace Drupal\theme_change\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\PathMatcher;
use Drupal\theme_change\Entity\ThemeChange;

/**
 * Class ThemeChangeswitcherNegotiator.
 *
 * @package Drupal\theme_change\Theme
 */
class ThemeChangeswitcherNegotiator implements ThemeNegotiatorInterface {

  /**
   * @var \Drupal\theme_change\Entity\ThemeChange
   */
  protected $themeChange;

  /**
   * @var string
   */
  protected $currentPath;

  /**
   * @var string
   */
  protected $currentPathAlias;

  /**
   * @var string
   */
  protected $currentRoute;
  protected $pathMatcher;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static function create($current_path, $alias_manager, $path_matcher, $current_route_match, $entity_type_manager) {
    return new static($current_path, $alias_manager, $path_matcher, $current_route_match, $entity_type_manager);
  }

  public function __construct(CurrentPathStack $current_path, AliasManager $alias_manager, PathMatcher $path_matcher, RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager) {
    // Set Current Path.
    $this->currentPath = $current_path->getPath();
    // Set Alias of path.
    $this->currentPathAlias = $alias_manager->getAliasByPath($this->currentPath);
    // Set Current Route name.
    $this->currentRoute = $current_route_match->getRouteName();
    // Set Path Matcher.
    $this->pathMatcher = $path_matcher;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($this->entityTypeManager->hasHandler('theme_change', 'list_builder')) {
      $storage = $this->entityTypeManager->getStorage('theme_change');
      $entities = $storage->loadMultiple();
      /** @var \Drupal\theme_change\Entity\ThemeChange $entity */
      foreach ($entities as $entity) {
        if ($this->check($entity)) {
          $this->themeChange = $entity;
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->themeChange->get_theme();
  }

  /**
   * @param $entity
   * @return bool
   */
  public function check(ThemeChange $entity) {
    if ($entity->get_type() == 'path') {
      $all_path = explode(',', $entity->get_path());
      foreach (array_map('trim', $all_path) as $path) {
        $path_match = $this->pathMatcher->matchPath($this->currentPath, $path);
        $path_alias_match = $this->pathMatcher->matchPath($this->currentPathAlias, $path);

        if ($path_match || $path_alias_match) {
          return TRUE;
        }
      }
    }
    else if ($entity->get_type() == 'route' && $this->currentRoute == $entity->get_route()) {
      return TRUE;
    }
    return FALSE;
  }

}
