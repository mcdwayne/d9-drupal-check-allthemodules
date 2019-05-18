<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Template entity.
 *
 * @ConfigEntityType(
 *   id = "sendwithus_template",
 *   label = @Translation("Template"),
 *   config_prefix = "template",
 *   admin_permission = "administer sendwithus",
 *   entity_keys = {
 *     "id" = "id",
 *     "key" = "key",
 *     "module" = "module",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class Template extends ConfigEntityBase {

  /**
   * The Template ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The email key.
   *
   * @var string
   */
  protected $key;

  /**
   * The template module.
   *
   * @var string
   */
  protected $module;

  /**
   * Gets the module.
   *
   * @return string
   *   The module.
   */
  public function getModule() : string {
    return $this->module;
  }

  /**
   * Gets the key.
   *
   * @return string
   *   The key.
   */
  public function getKey() : ? string {
    return $this->key;
  }

  /**
   * Sets the email key.
   *
   * @param string $key
   *   The key.
   *
   * @return \Drupal\sendwithus\Entity\Template
   *   The self.
   */
  public function setKey(string $key) : self {
    $this->set('key', $key);
    return $this;
  }

  /**
   * Sets the module.
   *
   * @param string $module
   *   The module.
   *
   * @return \Drupal\sendwithus\Entity\Template
   *   The self.
   */
  public function setModule(string $module) : self {
    $this->set('module', $module);
    return $this;
  }

}
