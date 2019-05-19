<?php

namespace Drupal\menu_link\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a widget for the menu_link field type.
 *
 * @FieldWidget(
 *   id = "menu_link_default",
 *   label = @Translation("Menu link"),
 *   field_types = {
 *     "menu_link"
 *   }
 * )
 */
class MenuLinkWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The parent menu link selector.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, MenuParentFormSelectorInterface $menu_parent_selector, AccountInterface $account, MenuLinkManagerInterface $menu_link_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->menuParentSelector = $menu_parent_selector;
    $this->account = $account;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('menu.parent_form_selector'),
      $container->get('current_user'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_weight = isset($items[$delta]->weight) ? $items[$delta]->weight : 0;

    $available_menu_names = array_filter($items->getSetting('available_menus'));
    $available_menus = array_combine($available_menu_names, $available_menu_names);

    if (empty($items[$delta]->menu_name)) {
      $default_menu_parent = $items->getSetting('default_menu_parent');
      if (empty($available_menus[rtrim($default_menu_parent, ':')])) {
        $default_menu_parent = reset($available_menu_names) . ':';
      }
    }
    else {
      $menu = $items[$delta]->menu_name;
      $parent = !empty($items[$delta]->parent) ? $items[$delta]->parent : '';
      $default_menu_parent = "$menu:$parent";
    }
    $default_title = isset($items[$delta]->title) ? $items[$delta]->title : NULL;
    $default_description = isset($items[$delta]->description) ? $items[$delta]->description : NULL;
    // The widget form may be used to define default values, so make sure the
    // form object is an entity form, rather than a configuration form.

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getParent()->getValue();
    $element['#pre_render'][] = [get_class($this), 'preRenderMenuDetails'];

    $element['#attached']['library'][] = 'menu_link/menu_link.form';

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu link title'),
      '#default_value' => $default_title,
      '#attributes' => ['class' => ['menu-link-title']],
    ];

    $element['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $default_description,
      '#rows' => 1,
      '#description' => $this->t('Shown when hovering over the menu link.'),
    ];

    $plugin_id = $items[$delta]->getMenuPluginId();
    $has_plugin = $plugin_id && $this->menuLinkManager->hasDefinition($plugin_id);
    $element['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Provide a menu link'),
      '#default_value' => (int) (bool) $has_plugin,
      '#attributes' => ['class' => ['menu-link-enabled']],
      '#multilingual' => FALSE,
    ];
    $element['menu'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu settings'),
      '#open' => (bool) $has_plugin,
      '#tree' => FALSE,
      '#weight' => $entity->getEntityTypeId() === 'node' ? -2 : 0,
      '#group' => $entity->getEntityTypeId() === 'node' ? 'advanced' : NULL,
      '#attributes' => ['class' => ['menu-link-form']],
      '#attached' => [
        'library' => ['menu_ui/drupal.menu_ui'],
      ],
    ];

    $plugin_id = $items[$delta]->getMenuPluginId();
    $parent_element = $this->menuParentSelector->parentSelectElement($default_menu_parent, $plugin_id, $available_menus);

    $element['menu_parent'] = $parent_element;
    $element['menu_parent']['#title'] = $this->t('Parent item');
    $element['menu_parent']['#attributes']['class'][] = 'menu-parent-select';

    $element['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#default_value' => $default_weight,
      '#description' => $this->t('Menu links with lower weights are displayed before links with higher weights.'),
    ];

    return $element;
  }

  /**
   * Pre-render callback: Builds a renderable array for the menu link widget.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public static function preRenderMenuDetails($element) {
    $element['menu']['enabled'] = $element['enabled'];
    $element['menu']['title'] = $element['title'];
    $element['menu']['description'] = $element['description'];
    $element['menu']['menu_parent'] = $element['menu_parent'];
    $element['menu']['weight'] = $element['weight'];

    // Hide the elements when enabled is disabled.
    foreach (['title', 'description', 'menu_parent', 'weight'] as $form_element) {
      $element['menu'][$form_element]['#states'] = [
        'invisible' => [
          'input[name="' . $element['menu']['enabled']['#name'] . '"]' => ['checked' => FALSE],
        ],
      ];
    }

    unset($element['enabled'], $element['title'], $element['description'], $element['menu_parent'], $element['weight']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);

    // Extract menu and parent menu link from single select element.
    foreach ($items as $delta => $item) {
      if (!empty($item->enabled) && !empty($item->menu_parent) && $item->menu_parent !== ':') {
        list($item->menu_name, $item->parent) = explode(':', $item->menu_parent, 2);
      }
      else {
        $item->title = '';
        $item->description = '';
        $item->menu_name = '';
        $item->parent = '';
      }
      unset($item->enabled, $item->menu_parent);
    }
  }

}
