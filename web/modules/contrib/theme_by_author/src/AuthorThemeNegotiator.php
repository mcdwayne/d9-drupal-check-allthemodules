<?php

namespace Drupal\theme_by_author;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Theme negotiator implementation based on the active entity's owner settings.
 */
class AuthorThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new AuthorThemeNegotiator object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * @inheritDoc
   */
  public function applies(RouteMatchInterface $route_match) {
    $author = $this->getAuthorFromRouteMatch($route_match);
    return !empty($author) && !empty($author->theme->value);
  }

  /**
   * @inheritDoc
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $author = $this->getAuthorFromRouteMatch($route_match);
    $theme = !empty($author->theme->value) ? $author->theme->value : NULL;
    $this->moduleHandler->alter('theme_by_author_active_theme', $theme, $route_match, $author);
    return $theme;
  }

  /**
   * Returns the entity owner's user entity.
   *
   * To get a result, the given route must match a canonical entity route and
   * that entity must have an owner defined.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return \Drupal\user\UserInterface|null
   *   The author of the given route's object. It must be a canonical entity
   *   route and the entity must implement \Drupal\user\EntityOwnerInterface
   *   in order to be able to determine an author at all.
   */
  protected function getAuthorFromRouteMatch(RouteMatchInterface $route_match) {
    $canonical_entity_route_pattern = '/entity\.(\w+)\.canonical/';
    $matches = [];
    preg_match($canonical_entity_route_pattern, $route_match->getRouteName(), $matches);
    if (!empty($matches)) {
      $entity_type = $matches[1];
      $entity = $route_match->getParameter($entity_type);
      if (!empty($entity) && $entity instanceof EntityOwnerInterface) {
        return $entity->getOwner();
      }
    }
    $authors = $this->moduleHandler->invokeAll('theme_by_author_route_author', [$route_match]);
    foreach ($authors as $author) {
      if ($author && $author instanceof UserInterface) {
        return $author;
      }
    }
    return NULL;
  }

}
