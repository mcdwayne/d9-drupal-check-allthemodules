<?php

namespace Drupal\git_issues\Plugin\GitIssues;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides the base issue type class.
 */
abstract class GitIssuesBase extends PluginBase implements GitIssuesPluginInterface {

  /**
   * Helper function that show/hide menu item.
   *
   * {@inheritdoc}
   */
  public function menuStateToggle($menuId, $enabled = TRUE) {
    $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $frontPageLink = $menuLinkManager->getDefinition($menuId);
    $frontPageLink['enabled'] = $enabled ? 1 : 0;
    $menuLinkManager->updateDefinition($menuId, $frontPageLink);
    $cache = \Drupal::cache('menu');
    $cache->deleteAll();
  }

}
