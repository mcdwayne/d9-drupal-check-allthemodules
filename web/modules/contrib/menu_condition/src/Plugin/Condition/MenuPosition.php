<?php

namespace Drupal\menu_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Menu position' condition.
 *
 * @Condition(
 *   id = "menu_position",
 *   label = @Translation("Menu position"),
 * )
 */
class MenuPosition extends ConditionPluginBase implements ConditionInterface, ContainerFactoryPluginInterface {

  /**
   * The menu active trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentFormSelector;

  /**
   * The plugin manager menu link service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $pluginManagerMenuLink;

  /**
   * Creates a MenuPosition instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The menu active trail service.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_form_selector
   *   The menu parent form selector service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $plugin_manager_menu_link
   *   The plugin manager menu link service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MenuActiveTrailInterface $menu_active_trail, MenuParentFormSelectorInterface $menu_parent_form_selector, MenuLinkManagerInterface $plugin_manager_menu_link) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuParentFormSelector = $menu_parent_form_selector;
    $this->pluginManagerMenuLink = $plugin_manager_menu_link;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.active_trail'),
      $container->get('menu.parent_form_selector'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    list($menu_name, $link_plugin_id) = explode(':', $this->configuration['menu_parent'], 2);

    $active_trail_ids = $this->menuActiveTrail->getActiveTrailIds($menu_name);

    // The condition evaluates to TRUE if the given menu link is in the active
    // trail.
    return isset($active_trail_ids[$link_plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    list($menu_name, $link_plugin_id) = explode(':', $this->configuration['menu_parent'], 2);
    $menu_link = $this->pluginManagerMenuLink->createInstance($link_plugin_id);
    return $this->t(
      'The menu item @link-title is either active or is in the active trail.', [
        '@link-title' => $menu_link->getTitle(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['menu_parent'] = $this->menuParentFormSelector->parentSelectElement($this->configuration['menu_parent']);
    $form['menu_parent']['#options'] = ['' => t("- None -")] + $form['menu_parent']['#options'];
    $form['menu_parent']['#title'] = t("Menu parent");
    $form['menu_parent']['#description'] = t("Show the block on this menu item and all its children.");

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['menu_parent'] = $form_state->getValue('menu_parent');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['menu_parent' => ''] + parent::defaultConfiguration();
  }

}
