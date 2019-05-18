<?php

namespace Drupal\maestro\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract the current user name
 *
 * No Options for this plugin.
 *
 * @ViewsArgumentDefault(
 *   id = "current_user_name",
 *   title = @Translation("Username from logged in user")
 * )
 */
class CurrentUserName extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return \Drupal::currentUser()->getAccountName();
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

}
