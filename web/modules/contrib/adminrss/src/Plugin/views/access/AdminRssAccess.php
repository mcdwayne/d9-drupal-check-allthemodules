<?php

namespace Drupal\adminrss\Plugin\views\access;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\adminrss\FeedAccess;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Class AdminRssAccess is the AdminRSS token-based Views access plugin.
 *
 * @ViewsAccess(
 *   id = "adminrss_access",
 *   title = @Translation("AdminRSS Access"),
 *   help = @Translation("Token-based access control.")
 * )
 */
class AdminRssAccess extends AccessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The adminrss.feed_access service.
   *
   * @var \Drupal\adminrss\FeedAccess
   */
  protected $feedAccess;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FeedAccess $feedAccess) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->feedAccess = $feedAccess;
  }

  /**
   * Determine if the current user has access or not.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user who wants to access this view.
   *
   * @return bool
   *   Returns whether the user has access to the view.
   */
  public function access(AccountInterface $account) {
    $token = $this->view->element['#arguments'][0];
    $access = $this->feedAccess->access($token);
    return $access;
  }

  /**
   * Allows access plugins to alter the route definition of a view.
   *
   * Likely the access plugin will add new requirements, so its custom access
   * checker can be applied.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to change.
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_adminrss_feed_access', 'TRUE');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $checker = $container->get('adminrss.feed_access');
    return new static($configuration, $plugin_id, $plugin_definition, $checker);
  }

}
