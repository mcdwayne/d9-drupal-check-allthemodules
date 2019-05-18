<?php

namespace Drupal\gridstack\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the base class for GridStack configuration entity.
 */
abstract class GridStackBase extends ConfigEntityBase implements GridStackBaseInterface {

  /**
   * The legacy CTools ID for the configurable optionset.
   *
   * @var string
   */
  protected $name;

  /**
   * The human-readable name for the optionset.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight to re-arrange the order of gridstack optionsets.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The plugin instance json to reduce frontend logic.
   *
   * @var string
   */
  protected $json = '';

  /**
   * The plugin instance options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::load('default')->getOptions('settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($group = NULL, $property = NULL) {
    $default = self::load('default');
    $options = $this->options ? array_merge($default->options, $this->options) : $default->options;
    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($options, $group);
      }
      elseif (isset($property) && isset($options[$group])) {
        return isset($options[$group][$property]) ? $options[$group][$property] : NULL;
      }
      return isset($options[$group]) ? $options[$group] : $options;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options = [], $merged = TRUE) {
    $this->options = $merged ? NestedArray::mergeDeep($this->options, $options) : $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($group) {
    // Makes sure to not call ::getOptions($group), else everything is dumped.
    return isset($this->getOptions()[$group]) ? $this->getOptions()[$group] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($group, $value) {
    $value = $group == 'settings' && isset($this->options[$group]) ? array_merge($this->options[$group], $value) : $value;
    $this->options[$group] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJson($group = '') {
    $default = self::load('default');
    if ($group) {
      $defaults = isset($default->json[$group]) ? $default->json[$group] : '';
      return $group && isset($this->json[$group]) ? $this->json[$group] : $defaults;
    }
    return $this->json;
  }

  /**
   * Load the optionset with a fallback.
   */
  public static function loadWithFallback($id) {
    $optionset = self::load($id);

    // Ensures deleted optionset while being used doesn't screw up.
    if (empty($optionset)) {
      $optionset = self::load('default');
    }
    return $optionset;
  }

}
