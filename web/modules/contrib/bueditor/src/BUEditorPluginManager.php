<?php

namespace Drupal\bueditor;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\bueditor\Entity\BUEditorEditor;

/**
 * Provides a plugin manager for BUEditor Plugins.
 *
 * @see \Drupal\bueditor\BUEditorPluginInterface
 * @see \Drupal\bueditor\BUEditorPluginBase
 * @see \Drupal\bueditor\Annotation\BUEditorPlugin
 * @see plugin_api
 */
class BUEditorPluginManager extends DefaultPluginManager {

  /**
   * Available plugin hooks.
   *
   * @var array
   */
  protected $hooks;

  /**
   * Available plugin instances.
   *
   * @var array
   */
  public $instances;

  /**
   * Constructs a BUEditorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/BUEditorPlugin', $namespaces, $module_handler, 'Drupal\bueditor\BUEditorPluginInterface', 'Drupal\bueditor\Annotation\BUEditorPlugin');
    $this->alterInfo('bueditor_plugin_info');
    $this->setCacheBackend($cache_backend, 'bueditor_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    // Sort definitions by weight
    uasort($definitions, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (isset($options['id']) && $id = $options['id']) {
      return isset($this->instances[$id]) ? $this->instances[$id] : $this->createInstance($id);
    }
  }

  /**
   * Returns all available plugin instances.
   *
   * @return array
   *   A an array plugin intances.
   */
  public function getInstances() {
    if (!isset($this->instances)) {
      $this->instances = [];
      foreach ($this->getDefinitions() as $id => $def) {
        $this->instances[$id] = $this->createInstance($id);
      }
    }
    return $this->instances;
  }

  /**
   * Returns available hooks.
   *
   * @return array
   *   An array of method names defined by plugin interface.
   */
  public function getHooks() {
    if (!isset($this->hooks)) {
      $this->hooks = get_class_methods('Drupal\bueditor\BUEditorPluginInterface');
    }
    return $this->hooks;
  }

  /**
   * Invokes a hook in all available plugins.
   *
   * @return array
   *   An array of results keyed by plugin id.
   */
  public function invokeAll($hook, &$a = NULL, $b = NULL, $c = NULL) {
    $ret = [];
    if (in_array($hook, $this->getHooks())) {
      foreach ($this->getInstances() as $plugin => $instance) {
        $ret[$plugin] = $instance->$hook($a, $b, $c);
      }
    }
    return $ret;
  }

  /**
   * Returns buttons defined by plugins.
   *
   * @return array
   *   An array of button definitions keyed by button ids.
   */
  public function getButtons() {
    $buttons = [];
    foreach ($this->getButtonGroups() as $plugin => $group) {
      $buttons = array_merge($buttons, $group['buttons']);
    }
    return $buttons;
  }

  /**
   * Returns buttons grouped by owner plugin.
   *
   * @return array
   *   An array of button lists keyed by plugin id.
   */
  public function getButtonGroups() {
    $plugin_buttons = [];
    $definitions = $this->getDefinitions();
    foreach ($this->invokeAll('getButtons') as $plugin => $buttons) {
      if ($buttons) {
        foreach ($buttons as $bid => $label) {
          $buttons[$bid] = (is_array($label) ? $label : ['label' => $label]) + ['id' => $bid];
        }
        $plugin_buttons[$plugin] = ['label' => $definitions[$plugin]['label'], 'buttons' => $buttons];
      }
    }
    return $plugin_buttons;
  }

  /**
   * Alters javascript data of a BUEditor Editor entity.
   */
  public function alterEditorJS(array &$js, BUEditorEditor $bueditor_editor, Editor $editor = NULL) {
    return $this->invokeAll('alterEditorJS', $js, $bueditor_editor, $editor);
  }

  /**
   * Alters a toolbar widget
   */
  public function alterToolbarWidget(array &$widget) {
    return $this->invokeAll('alterToolbarWidget', $widget);
  }

  /**
   * Alters a BUEditor Editor form.
   */
  public function alterEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor) {
    return $this->invokeAll('alterEditorForm', $form, $form_state, $bueditor_editor);
  }

  /**
   * Validates a BUEditor Editor form.
   */
  public function validateEditorForm(array &$form, FormStateInterface $form_state, BUEditorEditor $bueditor_editor) {
    return $this->invokeAll('validateEditorForm', $form, $form_state, $bueditor_editor);
  }
}