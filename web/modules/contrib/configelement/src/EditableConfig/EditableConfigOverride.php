<?php

namespace Drupal\configelement\EditableConfig;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\language\Config\LanguageConfigOverride;

class EditableConfigOverride extends EditableConfigWrapperBase implements EditableConfigWrapperInterface {

  /** @var \Drupal\language\Config\LanguageConfigOverride */
  protected $config;

  /** @var array */
  private $originalData;

  /**
   * EditableConfigWrapper constructor.
   *
   * @internal Use EditableConfigItemFactory::get
   *
   * @param \Drupal\language\Config\LanguageConfigOverride $config
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   */
  public function __construct(LanguageConfigOverride $config, TypedConfigManagerInterface $typedConfigManager) {
    parent::__construct($config, $typedConfigManager);
    $this->originalData = $config->get();
  }

  /**
   * @param \Drupal\Core\Config\Config $config
   *
   * @return \Drupal\configelement\EditableConfig\EditableConfigOverride
   */
  public static function create(LanguageConfigOverride $config) {
    return new static($config, \Drupal::service('config.typed'));
  }

  /**
   * @param array $element
   */
  public function addCachableDependencyTo(array &$element) {
    // Nothing to do here.
  }

  /**
   * @return array
   */
  public function getOriginalData() {
    return $this->originalData;
  }

  /**
   * @return array
   */
  protected function getConfigData() {
    return $this->config->get();
  }

  function relevantMappingDefinition($definition) {
    return parent::relevantMappingDefinition($definition)
      && !empty($definition['translatable']);
  }
}
