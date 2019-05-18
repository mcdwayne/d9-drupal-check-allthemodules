<?php

namespace Drupal\entity_list\Plugin\EntityListDisplay;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\entity_list\Element\RegionTable;
use Drupal\entity_list\Plugin\EntityListDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultEntityListDisplay.
 *
 * Override the getAvailableLayoutItems() method if you want to add, modify or
 * delete exposed items in the layout table (admin ui).
 * Make sure to take into account your changes in the
 * getRenderedLayoutItems|getRenderedItems|render (order by priority) methods
 * and override it if needed.
 *
 * This plugin depends on a EntityListQuery plugin
 *
 * @package Drupal\entity_list\Plugin\EntityListDisplay
 *
 * @EntityListDisplay(
 *   id = "default_entity_list_display",
 *   label = @Translation("Default entity list display")
 * )
 */
class DefaultEntityListDisplay extends EntityListDisplayBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  protected $entityDisplayRepository;

  protected $layoutPluginManager;

  /**
   * DefaultEntityListDisplay constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository to find available view mode.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, LayoutPluginManagerInterface $layout_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->layoutPluginManager = $layout_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('plugin.manager.core.layout')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $items, $view_mode = 'full', $langcode = NULL) {
    $render = [];

    $layout_plugin_id = $this->getLayout();
    if (!empty($layout_plugin_id)) {
      $layout_items = $this->getLayoutItems();
      try {
        $layout = $this->layoutPluginManager->createInstance($layout_plugin_id, []);
      }
      catch (PluginException $e) {
        watchdog_exception('plugin', $e);
      }
      if (!empty($layout)) {
        $render = $layout->build($this->getRenderedLayoutItems($items, $layout_items));
      }

    }

    $attr = new Attribute();
    $attr->addClass('entity-list');
    $attr->addClass($this->entity->id());
    if ($custom_class = $this->getCustomClass()) {
      $attr->addClass(explode(' ', $custom_class));
    }
    $render['#attributes'] = $attr;

    $query = $this->entity->getEntityListQueryPlugin();
    // Setup the cache.
    $render['#cache'] = [
      'tags' => [
        $query->getEntityTypeId() . '_list',
        'config:entity_list.entity_list.' . $this->entity->id(),
      ],
    ];

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $form = [];

    $form['custom_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional layout classes'),
      '#description' => $this->t('A space separated list of classes.'),
      '#default_value' => $form_state->getValue([
        'display',
        'custom_class',
      ], $this->getCustomClass()),
    ];

    $selected_layout = $form_state->getValue([
      'display',
      'layout',
    ], $this->getLayout());
    $form['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => $this->layoutPluginManager->getLayoutOptions(),
      '#default_value' => $selected_layout,
      '#ajax' => [
        'callback' => [
          get_class($form_state->getFormObject()),
          'update',
        ],
      ],
      '#ajax_update' => [
        'display-wrapper' => ['display_details', 'display'],
      ],
    ];

    $group_classes = [
      'weight' => 'group-order-weight',
      'region' => 'group-order-region',
    ];

    $form['layout_items'] = [
      '#type' => 'region_table',
      '#header' => $this->getLayoutHeader(),
      '#regions' => $this->buildRegions($selected_layout),
      '#tableselect' => FALSE,
      '#tabledrag' => $this->buildTableDrag($selected_layout, $group_classes),
      '#region_group' => $group_classes['region'],
    ];
    $form['layout_items'] += $this->buildLayoutItems($selected_layout, $group_classes, $form_state);

    return $form;
  }

  /**
   * Get the regions ready to use in the entity list table.
   *
   * @param string $layout
   *   A layout plugin id.
   *
   * @return array
   *   An array of region ready to use in the entity_list_table form element.
   */
  protected function buildRegions($layout) {
    $regions = [];
    try {
      $layout = $this->layoutPluginManager->getDefinition($layout);
    }
    catch (PluginNotFoundException $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('plugin', $e);
    }
    if (!empty($layout)) {
      foreach ($layout->getRegions() as $key => $region) {
        $regions[$key] = RegionTable::buildRowRegion($region['label'], $this->t('Empty region'));
      }
      if (!isset($regions['disable'])) {
        $regions['disable'] = RegionTable::buildRowRegion($this->t('Disable'), $this->t('No items disabled.'));
      }
    }
    return $regions;
  }

  /**
   * Get the region options according to the selected layout.
   *
   * @param string $layout
   *   The selected layout plugin id.
   *
   * @return array
   *   An array representing the available regions according to the selected
   *   layout. Ready to be used as a select form options.
   */
  protected static function getRegionOptions($layout) {
    $regions = [];
    try {
      /** @var \Drupal\Core\Layout\LayoutDefinition $layout */
      $layout = \Drupal::service('plugin.manager.core.layout')
        ->getDefinition($layout);
    }
    catch (PluginNotFoundException $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('plugin', $e);
    }
    if (!empty($layout)) {
      foreach ($layout->getRegions() as $key => $region) {
        $regions[$key] = $region['label'];
      }
      if (!isset($regions['disable'])) {
        $regions['disable'] = t('Disable');
      }
    }
    return $regions;
  }

  /**
   * Build the layout table items.
   *
   * @param string $selected_layout
   *   The selected layout.
   * @param array $group_classes
   *   The group_class used by the tabledrag.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the table rows.
   */
  protected function buildLayoutItems($selected_layout, array $group_classes, FormStateInterface $form_state) {
    $form_element = [];

    $default_values = $form_state->getValue([
      'display',
      'layout_items',
    ], $this->getLayoutItems());

    $layout_items = $this->getAvailableLayoutItems($default_values, $form_state);
    foreach ($layout_items as $key => $layout_item) {
      $label = $layout_item['label'];

      $row = RegionTable::buildRow(
        $label, $this->getRegionOptions($selected_layout),
        [get_class($this), 'getRowRegion'],
        $default_values[$key]['weight'] ?? 0,
        $default_values[$key]['region'] ?? 'disable');

      $row['#selected_layout'] = $selected_layout;

      $row['settings'] = $layout_item['settings'] ?? [];

      $row['weight']['#attributes']['class'] = [
        $group_classes['weight'],
        "{$group_classes['weight']}-disable",
      ];

      $row['region']['#attributes']['class'] = [
        $group_classes['region'],
        "{$group_classes['region']}-disable",
      ];

      $form_element[$key] = $row;
    }
    return $form_element;
  }

  /**
   * Build the tabledrag array.
   *
   * @param string $selected_layout
   *   The current layout plugin id.
   * @param array $group_classes
   *   An array containing region/weight classes.
   *
   * @return array
   *   An array representing the tabledrag values.
   */
  protected function buildTableDrag($selected_layout, array $group_classes) {
    $tabledrags = [];
    $regions = self::getRegionOptions($selected_layout);
    foreach ($regions as $region_name => $region) {
      $region_name_class = Html::getClass($region_name);
      $tabledrags[] = [
        'action' => 'match',
        'hidden' => TRUE,
        'relationship' => 'sibling',
        'group' => $group_classes['region'],
        'subgroup' => "{$group_classes['region']}-$region_name_class",
      ];
      $tabledrags[] = [
        'action' => 'order',
        'hidden' => TRUE,
        'relationship' => 'sibling',
        'group' => $group_classes['weight'],
        'subgroup' => "{$group_classes['weight']}-$region_name_class",
      ];
    }
    return $tabledrags;
  }

  /**
   * The #region_callback callback used by the region_table form element.
   *
   * @param array $row
   *   The current table row.
   *
   * @return string
   *   The region for the current row.
   */
  public static function getRowRegion(array &$row) {
    $regions = self::getRegionOptions($row['#selected_layout']);
    if (!isset($regions[$row['region']['#value']])) {
      $row['region']['#value'] = 'disable';
    }
    return $row['region']['#value'];
  }

  /**
   * Get the table header used in the layout section.
   *
   * @return array
   *   An array of string or translatable markup.
   */
  protected function getLayoutHeader() {
    return [
      $this->t('Label'),
      $this->t('Weight'),
      $this->t('Region'),
      $this->t('Settings'),
    ];
  }

  /**
   * Get the available layout items.
   *
   * @param array $default_values
   *   The default values from the form_state object or from the saved settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array representing the available items in the layout table.
   */
  protected function getAvailableLayoutItems(array $default_values, FormStateInterface $form_state) {
    $available_view_modes = [];
    $selected_query_plugin = $form_state->getValue(['query', 'plugin']);
    if (($query_plugin = $this->entity->getEntityListQueryPlugin($selected_query_plugin))) {
      // Here the query plugin settings come from the form_state object.
      $selected_bundles = array_filter($query_plugin->getBundles());
      $available_view_modes = $this->getAvailableViewModesOptions($query_plugin->getEntityTypeId(), $selected_bundles);
    }
    return [
      'total' => [
        'label' => $this->t('Total items'),
        'draggable' => TRUE,
        'settings' => [
          '#type' => 'details',
          '#title' => $this->t('Settings'),
          '#open' => FALSE,
          '#tree' => TRUE,
          'singular' => [
            '#type' => 'textfield',
            '#title' => $this->t('Singular'),
            '#description' => $this->t('Example: "1 item."'),
            '#default_value' => $default_values['total']['settings']['singular'] ?? '',
          ],
          'plural' => [
            '#type' => 'textfield',
            '#title' => $this->t('Plural'),
            '#description' => $this->t('Example: "@count items."'),
            '#default_value' => $default_values['total']['settings']['plural'] ?? '',
          ],
        ],
      ],
      'items' => [
        'label' => $this->t('Items'),
        'draggable' => TRUE,
        'settings' => [
          '#type' => 'details',
          '#title' => $this->t('Settings'),
          '#open' => FALSE,
          '#tree' => TRUE,
          'custom_class' => [
            '#type' => 'textfield',
            '#title' => $this->t('Additional classes'),
            '#description' => $this->t('A space separated list of classes.'),
            '#default_value' => $default_values['items']['settings']['custom_class'] ?? '',
          ],
          'custom_class_item' => [
            '#type' => 'textfield',
            '#title' => $this->t('Additional classes on items'),
            '#description' => $this->t('A space separated list of classes.'),
            '#default_value' => $default_values['items']['settings']['custom_class_item'] ?? '',
          ],
          'view_mode' => [
            '#type' => 'select',
            '#title' => $this->t('View mode'),
            '#options' => $available_view_modes,
            '#default_value' => $default_values['items']['settings']['view_mode'] ?? '',
          ],
          'empty' => [
            '#type' => 'textfield',
            '#title' => $this->t('Empty text'),
            '#description' => $this->t('The text used when the list is empty.'),
            '#default_value' => $default_values['items']['settings']['empty'] ?? '',
          ],
        ],
      ],
      'pager_1' => [
        'label' => $this->t('Pager 1'),
        'draggable' => TRUE,
      ],
      'pager_2' => [
        'label' => $this->t('Pager 2'),
        'draggable' => TRUE,
      ],
    ];
  }

  /**
   * Makes the intersection between the available view_modes for each bundles.
   *
   * @param string $entity_type
   *   The entity type id.
   * @param array $bundles
   *   An array of bundle ids.
   *
   * @return array
   *   An array of available view modes according to the selected bundles.
   */
  protected function getAvailableViewModesOptions($entity_type, array $bundles) {
    $view_modes = [];
    foreach ($bundles as $bundle) {
      $bundle_view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_type, $bundle);
      $view_modes = (empty($view_modes)) ? $bundle_view_modes : array_intersect($view_modes, $bundle_view_modes);
    }
    return $view_modes;
  }

  /**
   * Get layout custom class.
   *
   * @return string
   *   A space separated list of class.
   */
  public function getCustomClass() {
    return $this->settings['custom_class'] ?? '';
  }

  /**
   * Gets the layout plugin id.
   *
   * @return string
   *   A layout plugin id or empty.
   */
  public function getLayout() {
    return $this->settings['layout'] ?? 'default_entity_list_display';
  }

  /**
   * Gets the layout items weight/region/settings/etc...
   *
   * @return array
   *   An array of layout item info keyed by the item id.
   */
  public function getLayoutItems() {
    return $this->settings['layout_items'] ?? [];
  }

  /**
   * Get the rendered layout items per region.
   *
   * @param array $entities
   *   The current entities.
   * @param array $layout_items
   *   The layout items settings.
   *
   * @return array
   *   An array representing the layout items per region. Ready to be used with
   *   the layout's build method.
   */
  public function getRenderedLayoutItems(array $entities, array $layout_items) {
    // Group by region to match the layout build method.
    $rendered_items = [];

    $items = array_filter($layout_items, function ($layout_item) {
      return $layout_item['region'] !== 'disable';
    });

    $query_plugin = $this->entity->getEntityListQueryPlugin();

    foreach ($items as $key => $item) {
      $rendered_item = [];
      switch ($key) {
        case 'pager_1':
        case 'pager_2':
          if ($query_plugin->usePager()) {
            $rendered_item = [
              '#type' => 'pager',
            ];
          }
          break;

        case 'total':
          if (!empty($entities) && $query_plugin->usePager() && !empty($item['settings']['singular']) && !empty($item['settings']['plural'])) {
            $pager_info = entity_list_get_pager_infos();
            $rendered_item = [
              '#plain_text' => $this->formatPlural(
                $pager_info['total'] ?? 0,
                $item['settings']['singular'],
                $item['settings']['plural']
              ),
              '#prefix' => '<p class="total">',
              '#suffix' => '</p>',
            ];
          }
          break;

        case 'items':
          if (!empty($entities)) {
            $rendered_item = $this->getRenderedItems($entities);
            $attr = new Attribute();
            $attr->addClass('entity-list-item');
            if (!empty($item['settings']['custom_class_item'])) {
              $attr->addClass(explode(' ', $item['settings']['custom_class_item']));
            }
            foreach (Element::children($rendered_item) as $child) {
              $rendered_item[$child]['#prefix'] = '<li' . (string) $attr . '>';
              $rendered_item[$child]['#suffix'] = '</li>';
            }
          }
          elseif (!empty($item['settings']['empty'])) {
            $rendered_item = [
              '#plain_text' => $item['settings']['empty'],
            ];
          }
          if (!empty($item['settings']['custom_class'])) {
            $attributes = new Attribute();
            $attributes->addClass(explode(' ', $item['settings']['custom_class']));
            $rendered_items[$item['region']]['#attributes'] = $attributes;
          }
          $rendered_items[$item['region']]['#tag'] = !empty($entities) ? 'ul' : 'p';
          break;

      }
      $rendered_items[$item['region']][$key] = $rendered_item;
    }

    return $rendered_items;
  }

  /**
   * Return a render array representing entities.
   *
   * @param array $items
   *   An array representing list items as render array.
   */
  public function getRenderedItems(array $items) {
    $values = $this->getLayoutItems();
    $query_plugin = $this->entity->getEntityListQueryPlugin();
    $view_builder = $this->entityTypeManager->getViewBuilder($query_plugin->getEntityTypeId());
    return $view_builder->viewMultiple($items, $values['items']['settings']['view_mode'] ?? '');
  }

}
