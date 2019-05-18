<?php

namespace Drupal\js_component\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\js_component\JSComponentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define JS component block.
 *
 * @Block(
 *   id = "js_component",
 *   category = @Translation("JS Component"),
 *   admin_label = @Translation("JS Component"),
 *   deriver = "\Drupal\js_component\Plugin\Deriver\JSComponentsBlocksDeriver"
 * )
 */
class JSComponentBlockType extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * @var ElementInfoManagerInterface
   */
  protected $elementInfoManager;

  /**
   * @var JSComponentManagerInterface
   */
  protected $jsComponentManager;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'js_component' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * JS component block constructor.
   *
   * @param array $configuration
   *   The plugin configurations.
   * @param $plugin_id
   *   The plugin identifier.
   * @param $plugin_definition
   *   The plugin metadata definition.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param ElementInfoManagerInterface $element_info_manager
   *   The element information manager service.
   * @param \Drupal\js_component\JSComponentManagerInterface $js_component_manager
   *   The JS component manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LibraryDiscoveryInterface $library_discovery,
    ElementInfoManagerInterface $element_info_manager,
    JSComponentManagerInterface $js_component_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->libraryDiscovery = $library_discovery;
    $this->elementInfoManager = $element_info_manager;
    $this->jsComponentManager = $js_component_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('library.discovery'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.js_component')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['js_component'] = [
      '#type' => 'details',
      '#title' => $this->t('JS Component'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $this->attachSettings($form['js_component'], $form_state);

    if (count(Element::children($form['js_component'])) === 0) {
      unset($form['js_component']);
    }
    $form['#pre_render'][] = [
      get_class($this), 'preRenderJsComponent'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
    $component = $this->getComponentInstance();

    /** @var \Drupal\js_component\JSComponentFormInterface $settings_handler */
    if ($settings_handler = $component->settingsClassHandler()) {
      $settings_handler->validateComponentFormElements(
        $form, $form_state
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration = $form_state->cleanValues()->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = $this->buildComponentTemplate();

    if ($settings = $this->getComponentConfiguration()) {
      $settings = $this->recursiveCleanValues($settings);

      if (isset($build['#theme'])) {
        $build['#settings'] = $settings;
      }
      $root_id = $this->getComponentRootId();
      $plugin_id = $this->getComponentPluginId();

      $build['#attached']['drupalSettings']['jsComponent'][$plugin_id][$root_id] = [
        'settings' => $settings
      ];
    }

    if ($this->hasLibraryForComponent()) {
      $build['#attached']['library'][] = "js_component/{$this->getComponentId()}";
    }

    return $build;
  }

  /**
   * Pre render JS component callback.
   *
   * @param array $form
   *
   * @return mixed
   *   An array of element properties.
   */
  public static function preRenderJsComponent(array $form) {
    $form['js_component']['#parents'] = array_merge(
      $form['#parents'], ['js_component']
    );

    return $form;
  }

  /**
   * Build component template.
   *
   * @return array
   *   Am render array of the component template.
   */
  protected function buildComponentTemplate() {
    /** @var \Drupal\js_component\Plugin\JSComponent $component */
    $component = $this->getComponentInstance();

    if ($component->hasTemplate()) {
      return [
        '#theme' => $this->getComponentId(),
      ];
    }
    $root_id = $this->getComponentRootId();

    return [
      '#type' => 'inline_template',
      '#template' => '<div id="{{ root_id }}" class="{{ classes }}"></div>',
      '#context' => [
        'root_id' => $root_id,
        'classes' => implode(' ', $this->getBlockComponentClasses())
      ],
    ];
  }

  /**
   * JS component identifier.
   *
   * @return mixed
   */
  protected function getComponentId() {
    return $this->pluginDefinition['component_id'];
  }

  /**
   * Get component root identifier.
   *
   * @return string
   *   The component root identifier.
   */
  protected function getComponentRootId() {
    /** @var \Drupal\js_component\Plugin\JSComponent $component */
    $component = $this->getComponentInstance();

    $prefix = 'settings:';
    $root_id = $component->rootId();

    if (strpos($root_id, $prefix) !== FALSE) {
      $name = substr($root_id, strlen($prefix));
      $settings = $this->getComponentConfiguration();

      return isset($settings[$name])
        ? $settings[$name]
        : $root_id;
    }

    return $root_id;
  }

  /**
   * Get block components classes.
   *
   * @return array
   *   An array of the block component classes.
   */
  protected function getBlockComponentClasses() {
    $classes[] = 'js-component';
    $classes[] = 'js-component--' . Html::getClass($this->getComponentPluginId());

    return $classes;
  }

  /**
   * JS component instance.
   *
   * @return \Drupal\js_component\Plugin\JSComponent
   */
  protected function getComponentInstance() {
    return $this->jsComponentManager
      ->createInstance($this->getComponentPluginId());
  }

  /**
   * JS component plugin identifier.
   *
   * @return string
   *   The JS component plugin identifier.
   */
  protected function getComponentPluginId() {
    $plugin_id = $this->getPluginId();
    return substr($plugin_id, strpos($plugin_id, ':') + 1);
  }

  /**
   * JS component has libraries defined.
   *
   * @return bool
   *   Determine if the JS component has a library defined.
   */
  protected function hasLibraryForComponent() {
    $status = $this
      ->libraryDiscovery
      ->getLibraryByName('js_component', "{$this->getComponentId()}");

    return $status !== FALSE ? TRUE : FALSE;
  }

  /**
   * Recursive clean values.
   *
   * @param array $values
   *   An array of values.
   *
   * @return array
   *   An array of cleaned values.
   */
  protected function recursiveCleanValues(array $values) {
    foreach ($values as $key => &$value) {
      if (is_array($value)) {
        $value = $this->recursiveCleanValues($value);
      }
    }

    return array_filter($values);
  }

  /**
   * Attach JS component settings.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  protected function attachSettings(array &$form, FormStateInterface $form_state) {
    $component = $this->getComponentInstance();

    /** @var \Drupal\js_component\JSComponentFormInterface $settings_handler */
    if ($settings_handler = $component->settingsClassHandler()) {
      $settings_handler->attachComponentFormElements(
        $form, $form_state, $this->getComponentConfiguration()
      );
    }
    elseif ($component->hasSettings()) {
      $this->attachComponentFormElements($form);
    }
  }

  /**
   * Attach form elements.
   *
   * @param $form
   *   An array of form elements.
   */
  protected function attachComponentFormElements(&$form) {
    /** @var \Drupal\js_component\Plugin\JSComponent $component */
    $component = $this->getComponentInstance();
    $settings = $this->getComponentConfiguration();

    foreach ($component->settings() as $field_name => $field_info) {
      if (!isset($field_info['type'])
        || !$this->elementIsValid($field_info['type'])) {
        continue;
      }
      $element = $this->formatFormElement($field_info);

      if (isset($settings[$field_name])
        && !empty($settings[$field_name])) {
        $element['#default_value'] = $settings[$field_name];
      }

      $form[$field_name] = $element;
    }
  }

  /**
   * Format form element.
   *
   * @param array $element_info
   *   An array of the element key and value.
   *
   * @return array
   *   The formatted form element.
   */
  protected function formatFormElement(array $element_info) {
    $element = [];

    foreach ($element_info as $key => $value) {
      if (empty($value)) {
        continue;
      }
      $element["#{$key}"] = $value;
    }

    return $element;
  }

  /**
   * Form element is valid.
   *
   * @param $type
   *   The type of form element.
   *
   * @return bool
   *   Return TRUE if the element type is valid; otherwise FALSE.
   */
  protected function elementIsValid($type) {
    if (!$this->elementInfoManager->hasDefinition($type)) {
      return FALSE;
    }
    $element_type = $this
      ->elementInfoManager
      ->createInstance($type);

    return $element_type instanceof FormElementInterface;
  }

  /**
   * Get component configurations.
   *
   * @return array
   *   An array of component configurations.
   */
  protected function getComponentConfiguration() {
    return $this->getConfiguration()['js_component'];
  }
}
