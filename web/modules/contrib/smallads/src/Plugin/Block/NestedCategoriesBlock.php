<?php

namespace Drupal\smallads\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of category links, linked to the category view.
 *
 * The number of smallads in each appears in brackets.
 *
 * @Block(
 *   id = "smallad_categories_nested",
 *   admin_label = @Translation("Ads by Category"),
 *   category = @Translation("Lists (Views)")
 * )
 *
 * @note If the vocab is not hierarchical, this can be done with views.
 *
 * @todo Finish this with JQueryMenu
 */
class NestedCategoriesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Only grant access to users with the 'access news feeds' permission.
    return AccessResult::allowedIfHasPermission($account, 'post smallad');
  }

  /**
   * {@inheritdoc}
   *
   * @see http://pixelclever.com/official-documentation-jquery-menu-api
   *
   * @note watch out for the taxonomyblocks module
   */
  public function build() {
    return ['#markup' => 'Nested catgories block needs some serious javascript to work, such as jquerymenu'];
    // See version 7.x of offers_wants.
  }

}
