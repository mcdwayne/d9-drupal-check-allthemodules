<?php

namespace Drupal\rwd_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;

/**
 * Provides the RWD menu block.
 *
 * @Block(
 *   id = "rwd_menu",
 *   admin_label = @Translation("RWD menu trigger")
 * )
 */
class RwdMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $menuStorage;

  protected $menuTree;

  /**
   * Object constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   Menu tree handler object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, MenuLinkTreeInterface $menuTree) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->menuStorage = $entityTypeManager->getStorage('menu');
    $this->menuTree = $menuTree;
  }

  /**
   * Object create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   *   The new instance of this class.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'menu' => 'main',
      'css' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($this->configuration['menu']);

    // Expand all to show every link.
    $parameters->expandedParents = [];

    $manipulators = [
      // Show links to nodes that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree_items = $this->menuTree->load($this->configuration['menu'], $parameters);
    if (!empty($tree_items)) {
      $menu_tree = $this->menuTree->transform($tree_items, $manipulators);
      $build = $this->menuTree->build($menu_tree);

      // Use our own theme.
      $build['#theme'] = 'rwd_menu';
      $build['#css'] = $this->configuration['css'];
      return $build;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose which Drupal menu will be rendered as a responsive menu'),
      '#default_value' => $this->configuration['menu'],
      '#options' => $this->getMenuOptions(),
    ];
    $form['css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include default css'),
      '#default_value' => $this->configuration['css'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['menu'] = $form_state->getValue('menu');
  }

  /**
   * Gets a list of menu names for use as options.
   *
   * @param array $menu_names
   *   (optional) Array of menu names to limit the options, or NULL to load all.
   *
   * @return array
   *   Keys are menu names (ids) values are the menu labels.
   */
  protected function getMenuOptions(array $menu_names = NULL) {
    $menus = $this->menuStorage->loadMultiple($menu_names);
    $options = [];
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    return $options;
  }

}
