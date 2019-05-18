<?php

namespace Drupal\flags\Mapping;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Template\Attribute;

/**
 * Provides generic mapping service to map values to flags using config entities.
 *
 * Class BaseMapping
 */
abstract class BaseMapping implements FlagMappingInterface {

  /**
   * @var ImmutableConfig[]
   */
  protected $config;

  /**
   * @var string[]
   */
  protected $extraClasses = [];

  /**
   * Gets config key that holds list of mapping entities.
   *
   * @return string
   */
  protected abstract function getConfigKey();

  /**
   * Creates new instance of BaseMapping class.
   *
   * @param ConfigFactoryInterface $config
   */
  public function __construct(ConfigFactoryInterface $config) {
    $ids = $config->listAll($this->getConfigKey());

    $this->config = $config->loadMultiple($ids);
  }


  /**
   * {@inheritdoc}
   */
  function map($value) {
    // Unify input data
    $code = trim(strtolower($value));

    $key = $this->getConfigKey() . '.' . $code;

    if (isset($this->config[$key])) {
      // We make sure that flag is lowercase to match our CSS.
      return strtolower($this->config[$key]->get('flag'));
    }

    return $code;
  }

  /**
   * {@inheritDoc}
   */
  public function getOptionAttributes(array $options = []) {
    $attributes = [];

    foreach ($options as $key) {
      $classes = ['flag', 'flag-' . strtolower($this->map($key))];
      $classes = array_merge($this->getExtraClasses(), $classes);
      $attributes[$key] = new Attribute(['data-class' => $classes]);
    }

    return $attributes;
  }

  /**
   * {@inheritDoc}
   */
  public function getExtraClasses() {
    return $this->extraClasses;
  }

}
