<?php

namespace Drupal\views_evi;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Wraps an existing views filter.
 */
class ViewsEviFilterWrapper {

  /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filterHandler */
  protected $filterHandler;

  /**
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter_handler
   */
  function __construct($filter_handler) {
    $this->filterHandler = $filter_handler;
  }

  /**
   * Get filter handler.
   *
   * @return \Drupal\views\Plugin\views\filter\FilterPluginBase
   */
  public function getFilterHandler() {
    return $this->filterHandler;
  }

  /**
   * Get filter ID.
   *
   * @return string
   */
  public function getId() {
    return $this->filterHandler->options['id'];
  }

  /**
   * Get filter identifier a.k.a. $_GET key.
   *
   * @return string
   */
  public function getIdentifier() {
    return !empty($this->filterHandler->options['is_grouped']) ?
      $this->filterHandler->options['group_info']['identifier'] :
      $this->filterHandler->options['expose']['identifier'];
  }

  /**
   * Get filter label as input by user.
   *
   * @return string
   */
  public function getLabel() {
    return isset($this->filterHandler->options['expose']['label']) ?
      $this->filterHandler->options['expose']['label'] : '';
  }

  /**
   * Get filter label for use within EVI.
   *
   * @return string
   */
  public function getEviLabel() {
    // Provide label
    $identifier = $this->getIdentifier();
    $t_args = array('%identifier' => $identifier);
    $label = $this->getLabel();
    if ($label) {
      $t_args['%label'] = $label;
      $evi_label = new FormattableMarkup('%identifier (%label)', $t_args);
    }
    else {
      $evi_label = new FormattableMarkup('%identifier', $t_args);
    }
    return $evi_label;
  }

  /**
   * Get current display handler.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayPluginInterface
   */
  public function getDisplayHandler() {
    $view = $this->filterHandler->view;
    $display_handler = $view->displayHandlers->get($view->current_display);
    return $display_handler;
  }

  /**
   * Get EVI display extender class.
   *
   * @return \Drupal\views_evi\Plugin\views\display_extender\ViewsEviDisplayExtender
   */
  public function getEvi() {
    $display_handler = $this->getDisplayHandler();
    return $display_handler->getExtenders()['views_evi'];
  }

  /**
   * Get filter options.
   *
   * @param string $section
   *
   * @return array
   */
  public function getEviFilterOptions($section) {
    $display_handler = $this->getDisplayHandler();
    $section_options = $display_handler->getOption($section);
    $filter_id = $this->getId();

    $options = (array)@$section_options['filters'][$filter_id];
    return $options;
  }

  /**
   * Get plugin class name.
   *
   * @param string $plugin_type
   * @return string
   */
  public function getPluginId($plugin_type) {
    $filter_options = $this->getEviFilterOptions('views_evi_plugins');
    $plugin_class = @$filter_options[$plugin_type] ?: $this->getDefaultPluginClass($plugin_type);
    return $plugin_class;
  }

  /** @var \Drupal\views_evi\ViewsEviHandlerInterface[] $plugin */
  private $plugin;

  /**
   * Get plugin.
   *
   * @param string $plugin_type
   * @return ViewsEviHandlerInterface
   */
  public function getPlugin($plugin_type) {
    if (empty($this->plugin[$plugin_type])) {
      $plugin_id = $this->getPluginId($plugin_type);
      $this->plugin[$plugin_type] = $this->getPluginManager($plugin_type)->createInstance($plugin_id);
      $this->plugin[$plugin_type]->setFilterWrapper($this);
    }
    return $this->plugin[$plugin_type];
  }

  /**
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected function getPluginManager($plugin_type) {
    /** @var \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager */
    if ($plugin_type == 'value') {
      $plugin_manager = \Drupal::service('plugin.manager.views_evi.value');
    }
    if ($plugin_type == 'visibility') {
      $plugin_manager = \Drupal::service('plugin.manager.views_evi.visibility');
    }
    return $plugin_manager;
  }

  /**
   * Gets the default plugin ID.
   *
   * @param string $plugin_type
   *
   * @return string
   */
  public function getDefaultPluginClass($plugin_type) {
    $plugin_types = views_evi_plugin_types();
    return $plugin_types[$plugin_type]['default_plugin_id'];
  }

  /**
   * Get plugin settings.
   *
   * @param $plugin_type
   * @return array
   */
  public function getPluginSettings($plugin_type) {
    $filter_options = $this->getEviFilterOptions('views_evi_settings');
    $settings = @$filter_options[$plugin_type] ?: $this->getPlugin($plugin_type)->defaultSettings();
    return $settings;
  }

  /**
   * Get plugin settings by reference, for validating a partial array.
   *
   * @param string $plugin_type
   * @param array $all_options
   * @return array
   */
  public function &getPluginSettingsRef($plugin_type, &$all_options) {
    $filter_id = $this->getId();
    return $all_options['views_evi_settings']['filters'][$filter_id][$plugin_type];
  }

  /**
   * Set filter options.
   *
   * @param $section
   * @param array $filter_options
   * @return array
   */
  public function setEviFilterOptions($section, $filter_options) {
    $display_handler = $this->getDisplayHandler();
    $section_options = $display_handler->getOption($section);
    $filter_id = $this->getId();
    $section_options['filters'][$filter_id] = $filter_options;
    $display_handler->setOption($section, $section_options);
  }

  /**
   * Set plugin class.
   *
   * @param string $plugin_type
   * @param string $plugin_class
   */
  public function setPluginClass($plugin_type, $plugin_class) {
    $filter_options = $this->getEviFilterOptions('views_evi_plugins');
    $filter_options[$plugin_type] = $plugin_class;
    $this->setEviFilterOptions('views_evi_plugins', $filter_options);
  }

  /**
   * Set plugin settings.
   *
   * @param string $plugin_type
   * @param array $settings
   */
  public function setPluginSettings($plugin_type, $settings) {
    $filter_options = $this->getEviFilterOptions('views_evi_settings');
    $filter_options[$plugin_type] = $settings;
    $this->setEviFilterOptions('views_evi_settings', $filter_options);
  }

  /**
   * @return bool
   */
  public function getVisibility(&$form) {
    /** @var ViewsEviVisibilityInterface $visibility_handler */
    $visibility_handler = $this->getPlugin('visibility');
    $visibility = $visibility_handler->getVisibility($form);
    return $visibility;
  }

  /**
   * @return string|null
   */
  public function getValue() {
    /** @var ViewsEviValueInterface $value_handler */
    $value_handler = $this->getPlugin('value');
    $value = $value_handler->getValue();
    return $value;
  }

}
