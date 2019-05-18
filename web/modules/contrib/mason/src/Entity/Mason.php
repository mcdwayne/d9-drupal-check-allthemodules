<?php

namespace Drupal\mason\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Mason configuration entity.
 *
 * @ConfigEntityType(
 *   id = "mason",
 *   label = @Translation("Mason optionset"),
 *   list_path = "admin/structure/mason",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "options",
 *     "json",
 *   }
 * )
 */
class Mason extends ConfigEntityBase implements MasonInterface {

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
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'mason') {
    parent::__construct($values, $entity_type);
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($group = NULL, $property = NULL) {
    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($this->options, $group);
      }
      elseif (isset($property) && isset($this->options[$group])) {
        return isset($this->options[$group][$property]) ? $this->options[$group][$property] : NULL;
      }
      return isset($this->options[$group]) ? $this->options[$group] : $this->options;
    }
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option_name) {
    return isset($this->options[$option_name]) ? $this->options[$option_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getJson() {
    return $this->json;
  }

  /**
   * Returns HTML or layout related settings, none of JS to shutup notices.
   */
  public static function htmlSettings() {
    return [
      'background'   => TRUE,
      'id'           => '',
      'lightbox'     => '',
      'media_switch' => '',
      'optionset'    => 'default',
      'skin'         => '',
    ];
  }

}
