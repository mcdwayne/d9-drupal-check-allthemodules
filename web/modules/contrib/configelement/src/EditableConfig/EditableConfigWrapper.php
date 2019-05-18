<?php

namespace Drupal\configelement\EditableConfig;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Class EditableConfigWrapper
 *
 * Wraps a config object, can be shared by multiple config items, and can
 * autosave, triggered by EditableConfigItemFactory::triggetAutosave
 *
 * @package Drupal\configelement\EditableConfig
 */
class EditableConfigWrapper extends EditableConfigWrapperBase implements EditableConfigWrapperInterface {

  /** @var \Drupal\Core\Config\Config */
  protected $config;

  /**
   * EditableConfigWrapper constructor.
   *
   * @internal Use EditableConfigItemFactory::get
   *
   * @param \Drupal\Core\Config\Config $config
   */
  public function __construct(Config $config, TypedConfigManagerInterface $typedConfigManager) {
    parent::__construct($config, $typedConfigManager);
  }

  /**
   * @param \Drupal\Core\Config\Config $config
   *
   * @return \Drupal\configelement\EditableConfig\EditableConfigWrapper
   */
  public static function create(Config $config) {
    return new static($config, \Drupal::service('config.typed'));
  }

  /**
   * Add this as a cacheable dependency.
   *
   * @param array $element
   *   The render element.
   */
  public function addCachableDependencyTo(array &$element) {
    // @todo Inject.
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($element, $this->config);
  }

  /**
   * @return mixed
   */
  protected function getOriginalData() {
    return $this->config->getOriginal();
  }

  /**
   * @return array
   */
  protected function getConfigData() {
    return $this->config->getRawData();
  }

}
