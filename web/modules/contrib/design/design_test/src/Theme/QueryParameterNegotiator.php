<?php

/**
 * @file
 * Contains \Drupal\design_test\Theme\QueryParameterNegotiator.
 */

namespace Drupal\design_test\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Theme negotiator that uses the ?theme GET query parameter.
 */
class QueryParameterNegotiator implements ThemeNegotiatorInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new AjaxBasePageNegotiator.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // @todo Limit this to design_test routes, somehow.
    return (bool) $this->requestStack->getCurrentRequest()->query->has('theme');

    // Check whether the route was configured to use the base page theme.
    return ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT))
      && $route->hasOption('_theme')
      && $route->getOption('_theme') == 'ajax_base_page';
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->requestStack->getCurrentRequest()->query->get('theme');

    if (($ajax_page_state = $request->request->get('ajax_page_state'))  && !empty($ajax_page_state['theme']) && !empty($ajax_page_state['theme_token'])) {
      $theme = $ajax_page_state['theme'];
      $token = $ajax_page_state['theme_token'];

      // Prevent a request forgery from giving a person access to a theme they
      // shouldn't be otherwise allowed to see. However, since everyone is
      // allowed to see the default theme, token validation isn't required for
      // that, and bypassing it allows most use-cases to work even when accessed
      // from the page cache.
      if ($theme === $this->configFactory->get('system.theme')->get('default') || $this->csrfGenerator->validate($token, $theme)) {
        return $theme;
      }
    }
  }

}
