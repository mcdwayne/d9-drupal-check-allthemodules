<?php

namespace Drupal\owntracks\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides owntracks permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "owntracks",
 *   title = @Translation("OwnTracks"),
 *   help = @Translation("Access will be granted based on owntracks permissions.")
 * )
 */
class OwnTracks extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch */
    $currentRouteMatch = $container->get('current_route_match');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $currentRouteMatch
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    if ($account->hasPermission('administer owntracks') || $account->hasPermission('view any owntracks entity')) {
      return TRUE;
    }

    if ($account->hasPermission('view own owntracks entities')) {
      $user = $this->currentRouteMatch->getParameter('user');

      if ($user instanceof UserInterface) {
        $uid = $user->id();
      }
      else {
        $uid = $user;
      }

      if ($account->id() == $uid && !empty($uid)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_owntracks_user_map_access', 'TRUE');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
