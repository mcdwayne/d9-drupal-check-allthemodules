<?php

namespace Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_browser\FieldWidgetDisplayBase;
use Drupal\blazy\BlazyEntity;
use Drupal\slick_browser\SlickBrowser;
use Drupal\slick_browser\SlickBrowserDefault;
use Drupal\slick_browser\SlickBrowserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Slick Browser entity display and or entity selection.
 */
abstract class SlickBrowserFieldWidgetDisplayBase extends FieldWidgetDisplayBase implements ContainerFactoryPluginInterface {

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\BlazyEntity
   */
  protected $blazyEntity;

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\BlazyOEmbed
   */
  protected $blazyOembed;

  /**
   * The slick browser service.
   *
   * @var \Drupal\slick_browser\SlickBrowserInterface
   */
  protected $slickBrowser;

  /**
   * Constructs widget plugin.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyEntity $blazy_entity, SlickBrowserInterface $slick_browser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyEntity = $blazy_entity;
    $this->blazyOembed = $blazy_entity->oembed();
    $this->slickBrowser = $slick_browser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('blazy.entity'),
      $container->get('slick_browser')
    );
  }

  /**
   * Returns the blazy entity service.
   */
  public function blazyEntity() {
    return $this->blazyEntity;
  }

  /**
   * Returns the blazy oEmbed service.
   */
  public function blazyOembed() {
    return $this->blazyOembed;
  }

  /**
   * Returns the slick browser service.
   */
  public function slickBrowser() {
    return $this->slickBrowser;
  }

  /**
   * Returns the blazy admin service.
   */
  public function blazyAdmin() {
    return $this->slickBrowser->blazyAdmin();
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->slickBrowser->blazyManager();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $is_widget = isset($form['#fields']);
    $settings = $this->buildSettings(SlickBrowserDefault::baseFieldWidgetDisplaySettings());
    $definition = $this->getScopedFormElements();

    // This form can be either an Entity browser display, or widget plugin.
    $element = $this->blazyAdmin()->baseForm($definition);
    unset($element['media_switch'], $element['ratio'], $element['box_style']);
    if (empty($definition['image_style_form'])) {
      unset($element['image_style']);
    }

    if (isset($element['image_style'])) {
      $element['image_style']['#description'] = $this->t('Select image style to be used to display thumbnails, if applicable.');
    }

    if (isset($element['view_mode'])) {
      $element['view_mode']['#description'] = $this->t('Will fallback to this view mode, else entity label.');
      if ($this->getPluginId() == 'slick_browser_rendered_entity') {
        $element['view_mode']['#description'] = $this->t('Will use this view mode, else entity label.');
      }
    }

    foreach (['image_style', 'view_mode'] as $key) {
      if (isset($element[$key])) {
        $element[$key]['#default_value'] = $settings[$key];
      }
    }

    $element['_context'] = [
      '#type' => 'hidden',
      '#default_value' => $is_widget ? 'widget' : 'selection',
    ];

    // Do not use hook_field_widget_third_party_settings_form(), as this form is
    // also duplicated at "Manage form display" page.
    if ($is_widget) {
      $definition = $this->getScopedFormElements();
      $definition['style'] = TRUE;
      $this->slickBrowser->buildSettingsForm($element, $definition);
    }
    else {
      // This is the selection previews normally tiny thumbnails or labels.
      $element['selection_position'] = [
        '#type'    => 'select',
        '#title'   => $this->t('Selection position'),
        '#options' => [
          'left'        => $this->t('Left'),
          'right'       => $this->t('Right'),
          'bottom'      => $this->t('Bottom'),
          'over-bottom' => $this->t('Overlay bottom'),
        ],
        '#default_value' => isset($settings['selection_position']) ? $settings['selection_position'] : 'over-bottom',
        '#description'   => $this->t('Left and Right positions are more suitable for large displays such as Modal. Overlay means fixed positioned over the stage rather than sharing space. They are affected by the Slick Browser: Tabs positioning. If Tabs is placed at Top or Bottom, Selection position can be placed on the Right or Left, otherwise resulting in too narrow form. Adjust it accordingly.'),
      ];

      if (isset($element['image_style']['#description'])) {
        $element['image_style']['#description'] .= ' ' . $this->t('Images will be cropped by CSS regardless dimensions to avoid broken layout with certain positions.');
      }
    }

    unset($element['layout']);
    return $element;
  }

  /**
   * Gets EB widget settings.
   */
  public function buildSettings($defaults = []) {
    $defaults = $defaults ?: SlickBrowserDefault::widgetSettings();
    $settings = [];
    foreach ($defaults as $key => $default) {
      $settings[$key] = isset($this->configuration[$key]) ? $this->configuration[$key] : $default;
    }

    // No need for lightbox with tiny draggable items, set it to _basic.
    $settings['_basic'] = TRUE;

    // Only load the Blazy library if using SB browsers, but not SB widgets.
    $settings['_detached']    = !empty($settings['style']);
    $settings['entity_type']  = isset($this->configuration['entity_type']) ? $this->configuration['entity_type'] : '';
    $settings['media_switch'] = empty($settings['media_switch']) ? 'media' : $settings['media_switch'];

    // Enforces thumbnail without video iframes for tiny selection thumbnail.
    $selection = $settings['_context'] == 'selection';
    if ($selection) {
      $settings['lazy'] = $settings['ratio'] = '';
      $settings['_noiframe'] = $selection;
    }

    return array_merge(SlickBrowserDefault::entitySettings(), $settings);
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'settings' => $this->buildSettings(),
      'target_type' => $this->configuration['entity_type'],
    ] + SlickBrowser::scopedFormElements();
  }

}
