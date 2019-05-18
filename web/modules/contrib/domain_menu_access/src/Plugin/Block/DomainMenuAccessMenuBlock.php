<?php

namespace Drupal\domain_menu_access\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\system\Plugin\Block\SystemMenuBlock;

/**
 * Provides a Domain Access Menu block.
 *
 * @Block(
 *   id = "domain_access_menu_block",
 *   admin_label = @Translation("Domain Menu"),
 *   category = @Translation("Domain Menu"),
 *   deriver =
 *   "Drupal\domain_menu_access\Plugin\Derivative\DomainMenuAccessMenuBlock"
 * )
 */
class DomainMenuAccessMenuBlock extends SystemMenuBlock implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getDerivativeId();

    // Fallback on default menu if not restricted by domain.
    if (!$this->isDomainRestricted($menu_name)) {
      return parent::build();
    }
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters->setMinDepth($level);

    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'domain_menu_access.default_tree_manipulators:checkDomain'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    return $this->menuTree->build($tree);
  }

  /**
   * Check if domain access has been enabled on this menu.
   *
   * @param string $menu_name
   *   Menu name.
   *
   * @return bool
   *   Config enabled.
   */
  protected function isDomainRestricted($menu_name) {
    $config = \Drupal::config('domain_menu_access.settings')
      ->get('menu_enabled');

    return in_array($menu_name, $config);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['url.site']);
  }

}
