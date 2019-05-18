<?php

namespace Drupal\gridstack\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Serialization\Json;
use Drupal\gridstack\GridStackManagerInterface;
use Drupal\gridstack\Entity\GridStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a GridStack class for Layout plugins.
 */
class GridStackLayout extends LayoutDefault implements ContainerFactoryPluginInterface, LayoutInterface, PluginFormInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The gridstack manager service.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, GridStackManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('gridstack.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $config     = $this->getConfiguration();
    $definition = $this->getPluginDefinition();
    $id         = $definition->id();
    $item_id    = 'box';
    $name       = $definition->get('optionset');
    $optionset  = GridStack::load($name);
    $grids      = $optionset->getEndBreakpointGrids();
    $ipe_exists = $this->manager->getModuleHandler()->moduleExists('panels_ipe');
    $is_builder = $this->manager->getModuleHandler()->moduleExists('layout_builder');

    // Only check that Panels IPE is granted. Further access check is not here.
    // @see \Drupal\gridstack\GridStackManager::preRenderGridStack()
    $config['_ipe_panels'] = $config['_ipe_layouts'] = FALSE;
    if ($ipe_exists && $this->currentUser->hasPermission('access panels in-place editing')) {
      $config['_ipe_panels'] = TRUE;
    }

    if ($is_builder && $this->currentUser->hasPermission('configure any layout')) {
      $config['_ipe_layouts'] = TRUE;
    }

    // Converts string to array.
    $config['extras'] = empty($config['extras']) ? [] : Json::decode($config['extras']);

    // Defines settings.
    $settings = [
      'id'        => $id,
      'item_id'   => $item_id,
      'namespace' => 'gridstack',
      'optionset' => $name,
    ] + $config;

    // Converts gridstack breakpoint grids from stored JSON into array.
    $optionset->gridsJsonToArray($settings);

    $items = [];
    foreach ($grids as $delta => $grid) {
      $region = 'gridstack_' . $delta;
      $box    = [];

      $box_settings = $settings;
      unset($box_settings['attributes'], $box_settings['wrapper'], $box_settings['wrapper_classes']);
      $box['settings'] = $box_settings;

      $nested_grids = $optionset->getNestedGridsByDelta($delta);
      $is_nested = array_filter($nested_grids);

      if (!empty($is_nested)) {
        foreach ($nested_grids as $key => $nested_grid) {
          $nested_id = $delta . '_' . $key;
          $region = 'gridstack_' . $nested_id;

          // Preserves indices even if empty.
          $box[$item_id][$key][$item_id] = isset($regions[$region]) && !Element::isEmpty($regions[$region]) ? $regions[$region] : [];
        }
      }
      else {
        // Preserves indices even if empty.
        $box[$item_id] = isset($regions[$region]) && !Element::isEmpty($regions[$region]) ? $regions[$region] : [];
      }

      $items[] = $box;
      unset($box);
    }

    $build = [
      'items'     => $items,
      'optionset' => $optionset,
      'settings'  => $settings,
      'layout'    => $definition,
    ];

    return empty($items) ? [] : $this->manager->build($build);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'regions'         => [],
      'attributes'      => '',
      'skin'            => '',
      'wrapper'         => 'div',
      'wrapper_classes' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormElements(array $settings = []) {
    $wrapper_options = [
      'div'     => 'Div',
      'article' => 'Article',
      'aside'   => 'Aside',
      'figure'  => 'Figure',
      'header'  => 'Header',
      'main'    => 'Main',
      'footer'  => 'Footer',
      'section' => 'Section',
    ];

    $elements['wrapper'] = [
      '#type'          => 'select',
      '#options'       => $wrapper_options,
      '#title'         => $this->t('Wrapper'),
      '#default_value' => isset($settings['wrapper']) ? $settings['wrapper'] : 'div',
    ];

    $elements['wrapper_classes'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Classes'),
      '#description'   => $this->t('Use space: bg-dark text-white'),
      '#default_value' => isset($settings['wrapper_classes']) ? $settings['wrapper_classes'] : '',
    ];

    $elements['attributes'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Attributes'),
      '#description'   => $this->t('Use comma: role|main,data-key|value'),
      '#default_value' => isset($settings['attributes']) ? strip_tags($settings['attributes']) : '',
      '#weight'        => 20,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $regions = $form_state->getValue('regions');
    $settings = $form_state->getValue('settings');

    if (!empty($settings)) {
      foreach ($settings as $key => $value) {
        $this->configuration[$key] = trim(strip_tags($value));
      }

      unset($this->configuration['settings']);
    }

    if (!empty($regions)) {
      $stored_regions = [];
      foreach ($regions as $name => $info) {
        $region = $form_state->getValue(['regions', $name]);
        foreach ($region as $key => $value) {
          $stored_regions[$name][$key] = trim(strip_tags($value));
          if (empty($value)) {
            unset($stored_regions[$name][$key]);
          }
        }
      }

      $this->configuration['regions'] = $stored_regions;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Satisfy \Drupal\Core\Plugin\PluginFormInterface.
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // This form may be loaded as a subform by Field Layout, Panels, etc.
    // @see https://www.drupal.org/node/2536646
    // @see https://www.drupal.org/node/2798261
    // @see https://www.drupal.org/node/2774077
    // @todo: Remove when no more issues with it.
    if ($form_state instanceof SubformStateInterface) {
      $form_state = $form_state->getCompleteFormState();
    }

    $access      = $this->currentUser->hasPermission('administer gridstack');
    $config      = $this->getConfiguration();
    $definition  = $this->getPluginDefinition();
    $regions     = $definition->getRegions();
    $name        = $definition->get('optionset');
    $optionset   = GridStack::load($name);
    $regions_all = $optionset->prepareRegions(FALSE);
    $extras      = [];

    /** @var \Drupal\field_ui\Form\EntityViewDisplayEditForm $entity_form */
    $entity_form = $form_state->getFormObject();

    /* @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
    if (method_exists($entity_form, 'getEntity') && ($display = $entity_form->getEntity())) {
      $extras = [
        'bundle'      => $display->getTargetBundle(),
        'entity_type' => $display->getTargetEntityTypeId(),
        'view_mode'   => $display->getMode(),
      ];
    }

    $settings = [];
    foreach (['attributes', 'wrapper_classes', 'skin', 'wrapper'] as $key) {
      $default = $key == 'wrapper' ? 'div' : '';
      $default = isset($config[$key]) ? $config[$key] : $default;
      $settings[$key] = $form_state->getValue(['settings', $key], $default);
    }

    $prefix = '<h3>';
    if ($this->manager->getModuleHandler()->moduleExists('gridstack_ui') && $access) {
      $prefix .= $this->t('Outer wrapper settings [<small><a href=":url">Edit @id</a></small>]', [
        ':url' => $optionset->toUrl('edit-form')->toString(),
        '@id' => strip_tags($optionset->label()),
      ]);
    }
    else {
      $prefix .= $this->t('Outer wrapper settings');
    }
    $prefix .= '</h3>';

    $form['settings'] = [
      '#type'   => 'container',
      '#tree'   => TRUE,
      '#weight' => 20,
      '#prefix' => $prefix,
    ];

    $form['settings']['skin'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Skin'),
      '#options'       => $this->manager->getSkinOptions(),
      '#empty_option'  => $this->t('- None -'),
      '#description'   => $this->t('Choose a skin to load for this layout. Check out, or clone, gridstack_example.module to register skins here. Leave empty to disable.'),
      '#default_value' => $settings['skin'],
      '#prefix'        => '<div class="form-wrapper form-wrapper--inline form-wrapper--left">',
    ];

    $form['settings']['extras'] = [
      '#type'  => 'hidden',
      '#value' => empty($extras) ? '' : Json::encode($extras),
    ];

    $form['settings'] += $this->buildFormElements($settings);

    $closing = '</div><div class="form-wrapper form-wrapper--inline form-wrapper--icon form-wrapper--right">';
    if ($uri = $optionset->getIconUri()) {
      $image = [
        '#theme' => 'image',
        '#uri'   => $uri,
        '#alt'   => $this->t('Thumbnail'),
      ];
      $closing .= $this->manager->getRenderer()->render($image);
    }
    $closing .= '</div>';

    $form['settings']['attributes']['#suffix'] = $closing;

    $form['regions'] = [
      '#type'   => 'container',
      '#tree'   => TRUE,
      '#prefix' => '<p><small>' . $this->t('A region has direct contents. A container contains a single, or multiple regions. Avoid CSS class which may break this layout, taken care of by GridStack UI. Use cosmetic classes instead.') . '</small></p>',
    ];

    $settings = [];
    foreach ($regions_all as $region => $info) {
      foreach (['attributes', 'wrapper_classes', 'wrapper'] as $key) {
        $default = $key == 'wrapper' ? 'div' : '';
        $default = isset($config['regions'][$region][$key]) ? $config['regions'][$region][$key] : $default;
        $default = $form_state->getValue(['regions', $region, $key], $default);
        $settings['regions'][$region][$key] = $default;
      }

      $prefix = !array_key_exists($region, $regions) ? 'Container' : 'Region';

      $form['regions'][$region] = [
        '#type'       => 'details',
        '#title'      => $this->t('@prefix: <em>@label</em>', ['@prefix' => $prefix, '@label' => $info['label']]),
        '#open'       => TRUE,
        '#tree'       => TRUE,
        '#attributes' => ['class' => ['form-wrapper']],
      ];

      $form['regions'][$region] += $this->buildFormElements($settings['regions'][$region]);
    }

    $form['#attached']['library'][] = 'gridstack/admin_base';

    return $form;
  }

}
