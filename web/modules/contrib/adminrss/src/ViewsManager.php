<?php

namespace Drupal\adminrss;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\views\Entity\View;

/**
 * Class ViewsManager finds information about AdminRSS views.
 *
 * In some ways, it feels a bit like a plugin manager for non-plugin things
 * like View entities.
 */
class ViewsManager {
  const FEED_PLUGIN = 'feed';

  /**
   * The entity.query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The router.route_provider service.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The current access token.
   *
   * @var string
   */
  protected $token;

  /**
   * ViewsManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   The entity.query service.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The router.route_provider service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, QueryFactory $entityQuery, RouteProviderInterface $routeProvider) {
    $this->token = $configFactory->get(AdminRss::CONFIG)->get(AdminRss::TOKEN);
    $this->entityQuery = $entityQuery;
    $this->routeProvider = $routeProvider;
  }

  /**
   * Reducer: build all possible feed links for a given view.
   *
   * A view can expose multiple AdminRSS feeds, although none of the built-in
   * views do, so expose them all.
   *
   * @param array $carry
   *   A carry (accumulator) for links.
   * @param \Drupal\views\Entity\View $view
   *   The view from which to fetch links.
   *
   * @return array
   *   The resulting value for the carry.
   *
   * @see AdminRssSettingsForm::getFeedLinks()
   */
  protected function linksFromView(array $carry, View $view) {
    $tokenParam = ['adminrss_token' => $this->token];
    $displays = $view->get('display');
    $options = [
      'absolute' => TRUE,
      'attributes' => [],
    ];

    foreach ($displays as $display) {
      if ($display['display_plugin'] !== static::FEED_PLUGIN) {
        continue;
      }

      // Assumes a single route will always match. Does it need more checks ?
      $path = $display['display_options']['path'];
      $routeCollection = $this->routeProvider->getRoutesByPattern($path);
      $routes = reset($routeCollection);
      $routeName = key($routes);

      // ID is always present so no need to check.
      $title = $display['display_title'] ?? $display['id'];
      if (isset($display['display_options']['display_description'])) {
        $options['attributes']['title'] = $display['display_options']['display_description'];
      }

      $link = Link::createFromRoute($title, $routeName, $tokenParam, $options);
    }

    $carry[] = $link;
    return $carry;
  }

  /**
   * Get AdminRSS feed links.
   *
   * @return array
   *   An array of links to each AdminRSS view.
   */
  public function getFeedLinks() {
    $viewIds = $this->entityQuery
      ->get('view')
      ->condition('tag', 'AdminRSS')
      ->execute();
    $views = View::loadMultiple($viewIds);

    $linkBuilder = function ($carry, View $view) {
      return $this->linksFromView($carry, $view, $this->token);
    };
    $viewLinks = array_reduce($views, $linkBuilder, []);

    return $viewLinks;
  }

}
