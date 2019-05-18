<?php

namespace Drupal\ajax_loader;

use Drupal\Core\Plugin\PluginBase;

/**
 * Class ThrobberBase.
 */
abstract class ThrobberPluginBase extends PluginBase implements ThrobberPluginInterface {

  protected $path;
  protected $markup;
  /** @codingStandardsIgnoreLine. */
  protected $css_file;
  protected $label;

  /**
   * ThrobberPluginBase constructor.
   *
   * @param array $configuration
   *   Array with configuration.
   * @param string $plugin_id
   *   String with plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition value.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->path = '/' . drupal_get_path('module', 'ajax_loader');
    $this->markup = $this->setMarkup();
    $this->css_file = $this->setCssFile();
  }

  /**
   * Function to get markup.
   *
   * @return mixed
   *   Return markup.
   */
  public function getMarkup() {
    return $this->markup;
  }

  /**
   * Function to get css file.
   *
   * @return mixed
   *   Return the css file.
   */
  public function getCssFile() {
    return $this->css_file;
  }

  /**
   * Function to get label.
   *
   * @return mixed
   *   Return the label.
   */
  public function getLabel() {
    return $this->configuration['label'];
  }

  /**
   * Sets markup for throbber.
   */
  protected abstract function setMarkup();

  /**
   * Sets css file for throbber.
   */
  protected abstract function setCssFile();

}
