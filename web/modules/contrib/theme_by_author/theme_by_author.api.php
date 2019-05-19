<?php

/**
 * @file
 * Hooks related to Theme by author module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the active theme determined by this module's theme negotiator.
 *
 * @param string $theme
 *   The active theme as determined by this module's theme negotiator.
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match object.
 * @param \Drupal\user\UserInterface $author
 *   The author of the entity behind the current route.
 *
 * @see \Drupal\theme_by_author\AuthorThemeNegotiator::determineActiveTheme()
 */
function hook_theme_by_author_active_theme_alter(&$theme, \Drupal\Core\Routing\RouteMatchInterface $route_match, \Drupal\user\UserInterface $author) {
  if (empty($theme)) {
    // Define a fallback theme.
    $theme = 'my_cool_fallback_theme';
  }
}

/**
 * Determine the author for the given route.
 *
 * This alter hook is invoked during our theme negotiation implementation. The
 * theme negotiator only applies, if it is able to determine an author, and this
 * author must have a custom theme set. If no author has been found by our theme
 * negotiator internally - and only if none has been found - this hook will be
 * called, giving ohter modules the chance to determine the route's author.
 *
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match object.
 *
 * @return \Drupal\user\UserInterface|null
 *   An user entity that should be considered as author of the given route.
 *   If no author can be specified, NULL should be returned.
 *
 * @see \Drupal\theme_by_author\AuthorThemeNegotiator::getAuthorFromRouteMatch()
 */
function hook_theme_by_author_route_author(\Drupal\Core\Routing\RouteMatchInterface $route_match) {
  // Load the admin user as fallback.
  return \Drupal::entityTypeManager()->getStorage('user')->load(1);
}

/**
 * @} End of "addtogroup hooks".
 */
