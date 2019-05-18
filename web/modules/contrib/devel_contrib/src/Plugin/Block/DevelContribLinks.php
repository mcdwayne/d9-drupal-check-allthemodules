<?php

namespace Drupal\devel_contrib\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block showing development information on menu/task/action links.
 *
 * @Block(
 *   id = "devel_contrib_links",
 *   admin_label = @Translation("Menu links development"),
 *   category = @Translation("Development"),
 * )
 */
class DevelContribLinks extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $pluginManagerMenuLink;

  /**
   * The Local action manager service.
   *
   * @var \Drupal\Core\Menu\LocalActionManagerInterface
   */
  protected $pluginManagerMenuLocalAction;

  /**
   * The Local task manager service.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $pluginManagerMenuLocalTask;

  /**
   * Creates a DevelContribLinks instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $plugin_manager_menu_link
   *   The Menu link manager service.
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $plugin_manager_menu_local_action
   *   The Local action manager service.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $plugin_manager_menu_local_task
   *   The Local task manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MenuLinkManagerInterface $plugin_manager_menu_link,
    LocalActionManagerInterface $plugin_manager_menu_local_action,
    LocalTaskManagerInterface $plugin_manager_menu_local_task
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManagerMenuLink = $plugin_manager_menu_link;
    $this->pluginManagerMenuLocalAction = $plugin_manager_menu_local_action;
    $this->pluginManagerMenuLocalTask = $plugin_manager_menu_local_task;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.menu.link'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('plugin.manager.menu.local_task')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return 'Devel Links';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_route_name = \Drupal::service('current_route_match')->getRouteName();

    dsm($this->pluginManagerMenuLink->loadLinksByRoute($current_route_name));
    dsm($this->pluginManagerMenuLocalTask->getLocalTasksForRoute($current_route_name));
    dsm($this->pluginManagerMenuLocalAction->getActionsForRoute($current_route_name));

    return [];
  }

}
