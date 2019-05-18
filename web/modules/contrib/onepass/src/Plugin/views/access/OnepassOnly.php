<?php

namespace Drupal\onepass\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides access only for OnePass service servers.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "onepassonly",
 *   title = @Translation("OnePass access only"),
 *   help = @Translation("Allow access for OnePass service servers only.")
 * )
 */
class OnepassOnly extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * Onepass service.
   *
   * @var \Drupal\onepass\OnepassServiceInterface
   */
  protected $onepass;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->onepass = \Drupal::service('onepass.service');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->onepass->isRequestValid();
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_onepass', 'onepass');
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('OnePass access plugin');
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
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
