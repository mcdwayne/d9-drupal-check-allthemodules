<?php

namespace Drupal\geolocation\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Allow to display several field items on a common map.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "maps_common",
 *   title = @Translation("Geolocation CommonMap"),
 *   help = @Translation("Display geolocations on a common map."),
 *   theme = "views_view_list",
 *   display_types = {"normal"},
 * )
 */
class CommonMapBase extends StylePluginBase {

  protected $usesFields = TRUE;
  protected $usesRowPlugin = TRUE;
  protected $usesRowClass = FALSE;
  protected $usesGrouping = FALSE;

  protected $mapId = FALSE;

  protected $titleField = FALSE;
  protected $iconField = FALSE;

  /**
   * Map provider manager.
   *
   * @var \Drupal\geolocation\MapProviderManager
   */
  protected $mapProviderManager = NULL;

  /**
   * MapCenter options manager.
   *
   * @var \Drupal\geolocation\MapCenterManager
   */
  protected $mapCenterManager = NULL;

  /**
   * Data provider base.
   *
   * @var \Drupal\geolocation\DataProviderManager
   */
  protected $dataProviderManager = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $map_provider_manager, $map_center_manager, $data_provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->mapProviderManager = $map_provider_manager;
    $this->mapCenterManager = $map_center_manager;
    $this->dataProviderManager = $data_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.mapprovider'),
      $container->get('plugin.manager.geolocation.mapcenter'),
      $container->get('plugin.manager.geolocation.dataprovider')
    );
  }

  /**
   * Map update option handling.
   *
   * Dynamic map and client location and potentially others update the view by
   * information determined on the client site. They may want to update the
   * view result as well. So we need to provide the possible ways to do that.
   *
   * @return array
   *   The determined options.
   */
  protected function getMapUpdateOptions() {
    $options = [];

    foreach ($this->displayHandler->getOption('filters') as $filter_id => $filter) {
      if (
        !empty($filter['plugin_id'])
        && $filter['plugin_id'] == 'geolocation_filter_boundary'
      ) {
        /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter_handler */
        $filter_handler = $this->displayHandler->getHandler('filter', $filter_id);

        if ($filter_handler->isExposed()) {
          $options['boundary_filter_' . $filter_id] = $this->t('Boundary Filter') . ' - ' . $filter_handler->adminLabel();
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    return $this->options['even_empty'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    if (empty($this->options['geolocation_field'])) {
      \Drupal::messenger()->addMessage('The geolocation common map ' . $this->view->id() . ' views style was called without a geolocation field defined in the views style settings.', 'error');
      return [];
    }

    if (
      !empty($this->options['title_field'])
      && $this->options['title_field'] != 'none'
    ) {
      $this->titleField = $this->options['title_field'];
    }

    if (
      !empty($this->options['icon_field'])
      && $this->options['icon_field'] != 'none'
    ) {
      $this->iconField = $this->options['icon_field'];
    }

    // TODO: Not unique enough, but uniqueid() changes on every AJAX request.
    // For the geolocationCommonMapBehavior to work, this has to stay identical.
    $this->mapId = $this->view->id() . '-' . $this->view->current_display;
    $this->mapId = str_replace('_', '-', $this->mapId);

    $map_settings = [];
    if (!empty($this->options['map_provider_settings'])) {
      $map_settings = $this->options['map_provider_settings'];
    }

    $build = [
      '#type' => 'geolocation_map',
      '#maptype' => $this->options['map_provider_id'],
      '#id' => $this->mapId,
      '#settings' => $map_settings,
      '#attached' => [
        'library' => [
          'geolocation/geolocation.commonmap',
        ],
      ],
      '#context' => ['view' => $this->view],
    ];

    /*
     * Dynamic map handling.
     */
    if (!empty($this->options['dynamic_map']['enabled'])) {
      if (
        !empty($this->options['dynamic_map']['update_target'])
        && $this->view->displayHandlers->has($this->options['dynamic_map']['update_target'])
      ) {
        $update_view_display_id = $this->options['dynamic_map']['update_target'];
      }
      else {
        $update_view_display_id = $this->view->current_display;
      }

      $build['#attached']['drupalSettings']['geolocation']['commonMap'][$this->mapId]['dynamic_map'] = [
        'enable' => TRUE,
        'hide_form' => $this->options['dynamic_map']['hide_form'],
        'views_refresh_delay' => $this->options['dynamic_map']['views_refresh_delay'],
        'update_view_id' => $this->view->id(),
        'update_view_display_id' => $update_view_display_id,
      ];

      if (substr($this->options['dynamic_map']['update_handler'], 0, strlen('boundary_filter_')) === 'boundary_filter_') {
        $filter_id = substr($this->options['dynamic_map']['update_handler'], strlen('boundary_filter_'));
        $filters = $this->displayHandler->getOption('filters');
        $filter_options = $filters[$filter_id];
        $build['#attached']['drupalSettings']['geolocation']['commonMap'][$this->mapId]['dynamic_map'] += [
          'boundary_filter' => TRUE,
          'parameter_identifier' => $filter_options['expose']['identifier'],
        ];
      }
    }

    $this->renderFields($this->view->result);

    /*
     * Add locations to output.
     */
    foreach ($this->view->result as $row_number => $row) {
      foreach ($this->getLocationsFromRow($row) as $location) {
        $build['locations'][] = $location;
      }
    }

    $build = $this->mapCenterManager->alterMap($build, $this->options['centre'], $this);

    if ($this->view->getRequest()->get('geolocation_common_map_dynamic_view')) {
      if (empty($build['#attributes'])) {
        $build['#attributes'] = [];
      }
      $build['#attributes'] = array_replace_recursive($build['#attributes'], [
        'data-preserve-map-center' => TRUE,
      ]);
    }

    if ($this->mapProviderManager->hasDefinition($this->options['map_provider_id'])) {
      $build = $this->mapProviderManager
        ->createInstance($this->options['map_provider_id'], $this->options['map_provider_settings'])
        ->alterCommonMap($build, $this->options['map_provider_settings'], ['view' => $this]);
    }

    return $build;
  }

  /**
   * Render array from views result row.
   *
   * @param \Drupal\views\ResultRow $row
   *   Result row.
   *
   * @return array
   *   List of location render elements.
   */
  protected function getLocationsFromRow(ResultRow $row) {
    $locations = [];

    if (!empty($this->titleField)) {
      if (!empty($this->rendered_fields[$row->index][$this->titleField])) {
        $title_build = $this->rendered_fields[$row->index][$this->titleField];
      }
      elseif (!empty($this->view->field[$this->titleField])) {
        $title_build = $this->view->field[$this->titleField]->render($row);
      }
    }

    $icon_url = NULL;
    if (!empty($this->iconField)) {
      /** @var \Drupal\views\Plugin\views\field\Field $icon_field_handler */
      $icon_field_handler = $this->view->field[$this->iconField];
      if (!empty($icon_field_handler)) {
        $image_items = $icon_field_handler->getItems($row);
        if (!empty($image_items[0]['rendered']['#item']->entity)) {
          $file_uri = $image_items[0]['rendered']['#item']->entity->getFileUri();

          $style = NULL;
          if (!empty($image_items[0]['rendered']['#image_style'])) {
            /** @var \Drupal\image\Entity\ImageStyle $style */
            $style = ImageStyle::load($image_items[0]['rendered']['#image_style']);
          }

          if (!empty($style)) {
            $icon_url = file_url_transform_relative($style->buildUrl($file_uri));
          }
          else {
            $icon_url = file_url_transform_relative(file_create_url($file_uri));
          }
        }
      }
    }
    elseif (!empty($this->options['marker_icon_path'])) {
      $icon_token_uri = $this->viewsTokenReplace($this->options['marker_icon_path'], $this->rowTokens[$row->index]);
      $icon_token_uri = preg_replace('/\s+/', '', $icon_token_uri);
      $icon_url = file_url_transform_relative(file_create_url($icon_token_uri));
    }

    $data_provider = $this->dataProviderManager->createInstance($this->options['data_provider_id'], $this->options['data_provider_settings']);

    foreach ($data_provider->getPositionsFromViewsRow($row, $this->view->field[$this->options['geolocation_field']]) as $position) {
      $location = [
        '#type' => 'geolocation_map_location',
        'content' => $this->view->rowPlugin->render($row),
        '#title' => empty($title_build) ? '' : $title_build,
        '#position' => $position,
        '#weight' => $row->index,
        '#attributes' => ['data-views-row-index' => $row->index],
      ];

      if (!empty($icon_url)) {
        $location['#icon'] = $icon_url;
      }

      if (!empty($location_id)) {
        $location['#id'] = $location_id;
      }

      if ($this->options['marker_row_number']) {
        $markerOffset = $this->view->pager->getCurrentPage() * $this->view->pager->getItemsPerPage();
        $location['#label'] = (int) $markerOffset + (int) $row->index + 1;
      }

      $locations[] = $location;
    }

    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['even_empty'] = ['default' => '1'];

    $options['geolocation_field'] = ['default' => ''];
    $options['data_provider_id'] = ['default' => 'geolocation_field_provider'];
    $options['data_provider_settings'] = ['default' => []];

    $options['title_field'] = ['default' => ''];
    $options['icon_field'] = ['default' => ''];

    $options['marker_row_number'] = ['default' => FALSE];
    $options['dynamic_map'] = [
      'contains' => [
        'enabled' => ['default' => 0],
        'update_handler' => ['default' => ''],
        'update_target' => ['default' => ''],
        'hide_form' => ['default' => 0],
        'views_refresh_delay' => ['default' => '1200'],
      ],
    ];
    $options['centre'] = ['default' => []];
    $options['marker_icon_path'] = ['default' => ''];

    $options['map_provider_id'] = ['default' => ''];
    $options['map_provider_settings'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $map_provider_options = $this->mapProviderManager->getMapProviderOptions();

    if (empty($map_provider_options)) {
      $form = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => t("No map provider found."),
      ];
      return;
    }

    parent::buildOptionsForm($form, $form_state);

    $labels = $this->displayHandler->getFieldLabels();
    $geo_options = [];
    /** @var \Drupal\geolocation\DataProviderInterface[] $data_providers */
    $data_providers = [];
    $title_options = [];
    $icon_options = [];

    $fields = $this->displayHandler->getHandlers('field');
    /** @var \Drupal\views\Plugin\views\field\FieldPluginBase[] $fields */
    foreach ($fields as $field_name => $field) {
      $data_provider_settings = [];
      if (
        $this->options['geolocation_field'] == $field_name
        && !empty($this->options['data_provider_settings'])
      ) {
        $data_provider_settings = $this->options['data_provider_settings'];
      }
      if ($data_provider = $this->dataProviderManager->getDataProviderByViewsField($field, $data_provider_settings)) {
        $geo_options[$field_name] = $field->adminLabel();
        $data_providers[$field_name] = $data_provider;
      }

      if (!empty($field->options['type']) && $field->options['type'] == 'image') {
        $icon_options[$field_name] = $labels[$field_name];
      }

      if (!empty($field->options['type']) && $field->options['type'] == 'string') {
        $title_options[$field_name] = $labels[$field_name];
      }
    }

    $form['geolocation_field'] = [
      '#title' => $this->t('Geolocation source field'),
      '#type' => 'select',
      '#default_value' => $this->options['geolocation_field'],
      '#description' => $this->t("The source of geodata for each entity."),
      '#options' => $geo_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this->dataProviderManager), 'addDataProviderSettingsFormAjax'],
        'wrapper' => 'data-provider-settings',
        'effect' => 'fade',
      ],
    ];

    $data_provider = NULL;

    $form_state_data_provider_id = NestedArray::getValue($form_state->getUserInput(), ['style_options', 'geolocation_field']);
    if (
      !empty($form_state_data_provider_id)
      && !empty($data_providers[$form_state_data_provider_id])
    ) {
      $data_provider = $data_providers[$form_state_data_provider_id];
    }
    elseif (!empty($data_providers[$this->options['geolocation_field']])) {
      $data_provider = $data_providers[$this->options['geolocation_field']];
    }
    elseif ($data_providers[reset($geo_options)]) {
      $data_provider = $data_providers[reset($geo_options)];
    }
    else {
      return;
    }

    $form['data_provider_id'] = [
      '#type' => 'value',
      '#value' => $data_provider->getPluginId(),
    ];

    $form['data_provider_settings'] = [
      '#type' => 'container',
    ];

    if ($data_provider) {
      $form['data_provider_settings'] = $data_provider->getSettingsForm(
        $this->options['data_provider_settings'],
        [
          'style_options',
          'map_provider_settings',
        ]
      );
    }

    $form['data_provider_settings'] = array_replace($form['data_provider_settings'], [
      '#prefix' => '<div id="data-provider-settings">',
      '#suffix' => '</div>',
    ]);

    $form['title_field'] = [
      '#title' => $this->t('Title source field'),
      '#type' => 'select',
      '#default_value' => $this->options['title_field'],
      '#description' => $this->t("The source of the title for each entity. Field type must be 'string'."),
      '#options' => $title_options,
      '#empty_value' => 'none',
    ];

    $map_update_target_options = $this->getMapUpdateOptions();

    /*
     * Dynamic map handling.
     */
    if (!empty($map_update_target_options)) {
      $form['dynamic_map'] = [
        '#title' => $this->t('Dynamic Map'),
        '#type' => 'fieldset',
      ];
      $form['dynamic_map']['enabled'] = [
        '#title' => $this->t('Update view on map boundary changes. Also known as "AirBnB" style.'),
        '#type' => 'checkbox',
        '#default_value' => $this->options['dynamic_map']['enabled'],
        '#description' => $this->t("If enabled, moving the map will filter results based on current map boundary. This functionality requires an exposed boundary filter. Enabling AJAX is highly recommend for best user experience. If additional views are to be updated with the map change as well, it is highly recommended to use the view containing the map as 'parent' and the additional views as attachments."),
      ];

      $form['dynamic_map']['update_handler'] = [
        '#title' => $this->t('Dynamic map update handler'),
        '#type' => 'select',
        '#default_value' => $this->options['dynamic_map']['update_handler'],
        '#description' => $this->t("The map has to know how to feed back the update boundary data to the view."),
        '#options' => $map_update_target_options,
        '#states' => [
          'visible' => [
            ':input[name="style_options[dynamic_map][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['dynamic_map']['hide_form'] = [
        '#title' => $this->t('Hide exposed filter form element if applicable.'),
        '#type' => 'checkbox',
        '#default_value' => $this->options['dynamic_map']['hide_form'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[dynamic_map][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['dynamic_map']['views_refresh_delay'] = [
        '#title' => $this->t('Minimum idle time in milliseconds required to trigger views refresh'),
        '#description' => $this->t('Once the view refresh is triggered, any further change of the map bounds will have no effect until the map update is finished. User interactions like scrolling in and out or dragging the map might trigger the map idle event, before the user is finished interacting. This setting adds a delay before the view is refreshed to allow further map interactions.'),
        '#type' => 'number',
        '#min' => 0,
        '#default_value' => $this->options['dynamic_map']['views_refresh_delay'],
        '#states' => [
          'visible' => [
            ':input[name="style_options[dynamic_map][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      if ($this->displayHandler->getPluginId() !== 'page') {
        $update_targets = [
          $this->displayHandler->display['id'] => $this->t('- This display -'),
        ];
        foreach ($this->view->displayHandlers->getInstanceIds() as $instance_id) {
          $display_instance = $this->view->displayHandlers->get($instance_id);
          if ($display_instance->getPluginId() == 'page') {
            $update_targets[$instance_id] = $display_instance->display['display_title'];
          }
        }
        if (!empty($update_targets)) {
          $form['dynamic_map']['update_target'] = [
            '#title' => $this->t('Dynamic map update target'),
            '#type' => 'select',
            '#default_value' => $this->options['dynamic_map']['update_target'],
            '#description' => $this->t("Non-page displays will only update themselves. Most likely a page view should be updated instead."),
            '#options' => $update_targets,
            '#states' => [
              'visible' => [
                ':input[name="style_options[dynamic_map][enabled]"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }
      }
    }

    /*
     * Centre handling.
     */
    $form['centre'] = $this->mapCenterManager->getCenterOptionsForm((array) $this->options['centre'], $this);

    /*
     * Advanced settings
     */
    $form['advanced_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
    ];

    $form['even_empty'] = [
      '#group' => 'style_options][advanced_settings',
      '#title' => $this->t('Display map when no locations are found'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['even_empty'],
    ];

    if ($icon_options) {
      $form['icon_field'] = [
        '#group' => 'style_options][advanced_settings',
        '#title' => $this->t('Icon source field'),
        '#type' => 'select',
        '#default_value' => $this->options['icon_field'],
        '#description' => $this->t("Optional image (field) to use as icon."),
        '#options' => $icon_options,
        '#empty_value' => 'none',
        '#process' => [
          ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'],
          ['\Drupal\Core\Render\Element\Select', 'processSelect'],
        ],
        '#pre_render' => [
          ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'],
        ],
      ];
    }

    $form['marker_icon_path'] = [
      '#group' => 'style_options][advanced_settings',
      '#type' => 'textfield',
      '#title' => $this->t('Marker icon path'),
      '#description' => $this->t('Set relative or absolute path to custom marker icon. Tokens & Views replacement patterns supported. Empty for default.'),
      '#default_value' => $this->options['marker_icon_path'],
    ];

    $form['marker_row_number'] = [
      '#group' => 'style_options][advanced_settings',
      '#title' => $this->t('Show views result row number in marker'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['marker_row_number'],
    ];

    $form['map_provider_id'] = [
      '#type' => 'select',
      '#options' => $map_provider_options,
      '#title' => $this->t('Map Provider'),
      '#default_value' => $this->options['map_provider_id'],
      '#ajax' => [
        'callback' => [get_class($this->mapProviderManager), 'addSettingsFormAjax'],
        'wrapper' => 'map-provider-settings',
        'effect' => 'fade',
      ],
    ];

    $form['map_provider_settings'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t("No settings available."),
    ];

    $map_provider_id = NestedArray::getValue($form_state->getUserInput(), ['style_options', 'map_provider_id']);
    if (empty($map_provider_id)) {
      $map_provider_id = $this->options['map_provider_id'];
    }
    if (empty($map_provider_id)) {
      $map_provider_id = key($map_provider_options);
    }

    $map_provider_settings = NestedArray::getValue($form_state->getUserInput(), ['style_options', 'map_provider_settings']);
    if (empty($map_provider_settings)) {
      $map_provider_settings = $this->options['map_provider_settings'];
    }

    if (!empty($map_provider_id)) {
      $form['map_provider_settings'] = $this->mapProviderManager
        ->createInstance($map_provider_id, $map_provider_settings)
        ->getSettingsForm(
          $map_provider_settings,
          [
            'style_options',
            'map_provider_settings',
          ]
        );
    }

    $form['map_provider_settings'] = array_replace(
      $form['map_provider_settings'],
      [
        '#prefix' => '<div id="map-provider-settings">',
        '#suffix' => '</div>',
      ]
    );
  }

}
