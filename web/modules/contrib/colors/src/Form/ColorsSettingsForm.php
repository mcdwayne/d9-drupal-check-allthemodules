<?php

namespace Drupal\colors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\colors\Plugin\ColorsSchemePluginCollection;

/**
 * Configure color settings.
 */
class ColorsSettingsForm extends ConfigFormBase {

  /**
   * An array of configuration names that should be editable.
   *
   * @var array
   */
  protected $editableConfig = [];

  /**
   * Stores the Colors Scheme plugins.
   *
   * @var \Drupal\colors\Plugin\ColorsSchemePluginCollection
   */
  protected $pluginCollection;


  /**
   * Constructs a ColorsSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $manager) {
    parent::__construct($config_factory);

    $this->pluginCollection = new ColorsSchemePluginCollection($manager, $this);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.colors')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colors_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return $this->editableConfig;
  }

  /**
   *
   * @return \Drupal\colors\Plugin\ColorsSchemePluginCollection;|\Drupal\colors\Plugin\ColorsSchemeinterface[]
   */
  public function getPlugins() {
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Color picker.
    $form = colors_load_colorpicker();
    $form['#attached'] = array(
      'library' => array(
        'colors/colors',
      ),
    );

    $entity = $this->getEntityFromRoute(\Drupal::routeMatch());
    $plugins = \Drupal::service('plugin.manager.colors')->getDefinitions();
    $plugin = $plugins[$entity];

    // Global settings tab.
    if (!$plugin) {
      $this->getDefaultFormElements($form);
    }
    else {
      $this->getFormElements($form, $entity);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin = $form_state->getValue('plugin');
    if ($plugin) {
      foreach ($form_state->getValue('palette') as $id => $palette) {
        \Drupal::configFactory()->getEditable($id)
          ->set('type', $plugin['id'])
          ->set('label', $plugin['title'])
          ->set('enabled', $form_state->getValue($form_state->getValue('id')))
          ->set('palette', $palette)
          ->set('weight', $plugin['weight'])
          ->save();
      }
      // Update settings.
      $config = \Drupal::configFactory()->getEditable('colors.settings');
      $order = $config->get('order');
      if ($form_state->getValue($form_state->getValue('id'))) {
        // Enabled.
        $order[$plugin['id']] = 0;
      }
      else {
        // Disabled.
        unset($order[$plugin['id']]);
      }
      $config->set('order', $order)->save();
    }
    else {
      $process = $form_state->getValue('process_order');
      \Drupal::configFactory()->getEditable('colors.settings')
        ->set('override', $process['enabled'])
        ->set('order', $process['enabled'] ? colors_get_weights($form_state) : [])
        ->set('palette', $form_state->getValue('palette'))
        ->save();
    }
  }

  /**
   * Get form element for default settings.
   *
   * @param array $form
   */
  protected function getDefaultFormElements(&$form) {
    $config = colors_get_info();
    $palette = colors_get_palette($config);
    $names = $config->get('fields');

    $form['palette']['#tree'] = TRUE;
    foreach ($palette as $name => $value) {
      if (isset($names[$name])) {
        $form['palette'][$name] = array(
          '#type' => 'textfield',
          '#title' => $names[$name],
          '#value_callback' => 'color_palette_color_value',
          '#default_value' => $value,
          '#size' => 8,
          '#attributes' => array('class' => array('colorpicker-input')),
        );
      }
    }

    $form['process_order'] = [
      '#tree' => TRUE,
      'info' => [
        '#type' => 'item',
        '#title' => t('Process order'),
      ],
      'enabled' => [
        '#type' => 'checkbox',
        '#title' => t('Change the CSS processing order.'),
        '#default_value' => \Drupal::configFactory()->getEditable('colors.settings')->get('override'),
        '#description' => t('Color order is cascading, CSS from modules at the bottom will override the top.'),
      ],
    ];

    $form['order_settings'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="process_order[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['order_settings']['entities'] = [
      '#type' => 'table',
      '#header' => [$this->t('Name'), $this->t('Weight')],
      '#empty' => $this->t('No entities available.'),
      '#attributes' => [
        'id' => 'colors-entities',
      ],
    ];

    $configs = \Drupal::configFactory()
      ->getEditable('colors.settings')
      ->get('order');
    foreach ($configs as $config_id => $weight) {
      $plugin = \Drupal::service('plugin.manager.colors')
        ->getDefinition($config_id);

      $form['order_settings']['entities'][$config_id]['entity'] = ['#plain_text' => $plugin['title']];
      $form['order_settings']['entities'][$config_id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for added entity'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => [
          'class' => ['color-weight'],
        ],
      ];

      $form['order_settings']['entities'][$config_id]['#attributes']['class'][] = 'draggable';

      $form['order_settings']['entities']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'color-weight',
      ];
    }
  }

  /**
   * Get settings form elements.
   *
   * @param array $form
   * @param string $entity
   */
  protected function getFormElements(&$form, $entity) {
    $plugin = \Drupal::service('plugin.manager.colors')->getDefinition($entity);
    if (!$plugin['id']) {
      return;
    }

    $form[$plugin['id']] = [
      '#type' => 'item',
      '#title' => t('@title colors', ['@title' => $plugin['title']]),
      '#description' => $plugin['description'],
    ];

    $multiple = !empty($plugin['multiple']);
    $repeat = !empty($multiple) ? $plugin['multiple']() : [NULL => NULL];

    $enabled = colors_get_enabled($entity) ? TRUE : FALSE;

    foreach ($repeat as $id => $repeat_value) {
      $config_name = "colors-$entity";
      $config_name .= !empty($multiple) ? "-$id" : '';

      $element = [
        '#type' => 'details',
        '#title' => !empty($multiple) ? $repeat_value->label() : t('@title colors', array('@title' => $plugin['title'])),
        '#collapsible' => TRUE,
        '#open' => $enabled,
        '#attributes' => ['class' => ['overflow-hidden']],
        '#prefix' => '<div class="clearfix">',
        '#suffix' => '</div>',
      ];

      $element[$config_name] = [
        '#type' => 'checkbox',
        '#title' => $plugin['label'],
        '#default_value' => $enabled,
      ];

      $element['plugin'] = [
        '#type' => 'value',
        '#value' => $plugin,
      ];

      $element['id'] = [
        '#type' => 'value',
        '#value' => $config_name,
      ];

//      @todo: don't display vocab fieldset if there's no terms yet.
      foreach ($plugin['callback']($id) as $key => $label) {
        //$config_id = "colors.$entity.$key";
        $config_id = str_replace('-', '.', $config_name) . ".$key";

        $element['palette'][$config_id] = [
          '#type' => 'details',
          '#title' => t($label),
          '#collapsible' => TRUE,
          '#open' => TRUE,
          '#attributes' => ['class' => ['views-left-25']],
          '#states' => [
            'visible' => [
              ':input[name="' . $config_name . '"]' => ['checked' => TRUE],
            ],
          ],
        ];

        // Get configs.
        $default_config = colors_get_info();
        $config = colors_get_info($config_id);
        $config = ($config->isNew()) ? $default_config : $config;
        // Get palette.
        $palette = colors_get_palette($config);

        $names = $default_config->get('fields');

        $element['palette']['#tree'] = TRUE;

        foreach ($palette as $name => $value) {
          if (isset($names[$name])) {
            $element['palette'][$config_id][$name] = [
              '#type' => 'textfield',
              '#title' => $names[$name],
              '#value_callback' => 'color_palette_color_value',
              '#default_value' => $value,
              '#maxlength' => 7,
              '#size' => 7,
              '#attributes' => ['class' => ['colorpicker-input']],
              '#states' => [
                'visible' => [
                  ':input[name="' . $config_name . '"]' => ['checked' => TRUE],
                ],
              ],
            ];
          }
        }
      }

      $element_key = !empty($multiple) ? $id : 'details';
      $form[$element_key] = $element;
    }
  }

  // @todo: service
  public function getEntityFromRoute($route) {
    $part = '';
    $route_name = $route->getRouteName();
    if ($route_name === 'colors.ui_settings_entity.users') {
      $part .= 'user_current';
    }
    elseif ($route_name !== 'colors.ui_settings') {
      $parts = explode('/', $route->getRouteObject()->getPath());
      $part .= end($parts);
    }
    return $part;
  }

}
