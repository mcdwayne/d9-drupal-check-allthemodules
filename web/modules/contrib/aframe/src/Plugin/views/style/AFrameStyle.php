<?php

namespace Drupal\aframe\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\aframe\AFrameComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "aframe",
 *   title = @Translation("A-Frame scene"),
 *   help = @Translation("@TODO"),
 *   theme = "views_view_aframe",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class AFrameStyle extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = FALSE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * The AFrame component manager.
   *
   * @var \Drupal\aframe\AFrameComponentPluginManager
   */
  protected $componentManager;

  /**
   * Constructs an AFrameStyle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\aframe\AFrameComponentPluginManager $component_manager
   *   The AFrame component manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AFrameComponentPluginManager $component_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->componentManager = $component_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.aframe.component')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['aframe_components'] = [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $element['aframe_components'] = [
      '#type'  => 'details',
      '#title' => t('Components'),
      '#open'  => TRUE,
    ];

    /** @var \Drupal\aframe\AFrameComponentPluginManager $component_manager */
    $component_manager = $this->componentManager;
    $components = $component_manager->getDefinitions();
    foreach ($components as $component) {
      /** @var \Drupal\aframe\AFrameComponentPluginInterface $component_instance */
      $component_instance = $component_manager->createInstance($component['id'], [
        'settings' => [
          $component['id'] => $this->options['aframe_components'][$component[id]],
        ],
      ]);
      $form['aframe_components'][$component['id']] = $component_instance->settingsForm($form, $form_state);
    }

  }

}
