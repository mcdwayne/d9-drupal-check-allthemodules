<?php

namespace Drupal\accordion_menus\Plugin\Block;

use Drupal\Core\Link;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a accordion Menu block.
 *
 * @Block(
 *   id = "accordion_menus_block",
 *   admin_label = @Translation("Accordion Menus"),
 *   category = @Translation("Accordion Menus"),
 *   deriver = "Drupal\accordion_menus\Plugin\Derivative\AccordionMenusBlock"
 * )
 */
class AccordionMenusBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * Constructs a new AccordionMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MenuLinkTreeInterface $menu_tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $elements = [];
    $menu_name = $this->getDerivativeId();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMinDepth(0)->onlyEnabledLinks();

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    foreach ($tree as $key => $menu_item) {
      if ($menu_item->hasChildren) {
        $elements[$key] = [
          'content' => $this->generateSubMenuTree($menu_item->subtree),
          'title' => $menu_item->link->getTitle(),
        ];
      }
    }

    return [
      '#theme' => 'accordian_menus_block',
      '#elements' => $elements,
      '#attached' => ['library' => ['accordion_menus/accordion_menus_widget']],
    ];
  }

  /**
   * Generate submenu output.
   */
  public function generateSubMenuTree($sub_menus) {
    $items = [];
    foreach ($sub_menus as $sub_menu) {
      // If menu element disabled skip this branch.
      if ($sub_menu->link->isEnabled()) {
        $items[] = Link::fromTextAndUrl($sub_menu->link->getTitle(), $sub_menu->link->getUrlObject());
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

}
