<?php

namespace Drupal\entity_list\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for defining Entity list entities.
 */
interface EntityListInterface extends ConfigEntityInterface {

  /**
   * Get the entity list display plugin id.
   *
   * @param string $default
   *   The default value used as fallback (mainly used in the EntityListForm).
   *
   * @return string
   *   The plugin id.
   */
  public function getEntityListDisplayPluginId($default = '');

  /**
   * Get the entity list display plugin instance.
   *
   * @param string $default
   *   The default value used as fallback (mainly used in the EntityListForm).
   *
   * @return null|\Drupal\entity_list\Plugin\EntityListDisplayInterface
   *   A entity list display plugin instance.
   */
  public function getEntityListDisplayPlugin($default = '');

  /**
   * Get the entity list query plugin id.
   *
   * @param string $default
   *   The default value used as fallback (mainly used in the EntityListForm).
   *
   * @return string
   *   The plugin id.
   */
  public function getEntityListQueryPluginId($default = '');

  /**
   * Get the entity list query plugin instance.
   *
   * @param string $default
   *   The default value used as fallback (mainly used in the EntityListForm).
   *
   * @return null|\Drupal\entity_list\Plugin\EntityListQueryInterface
   *   A entity list display plugin instance.
   */
  public function getEntityListQueryPlugin($default = '');

  /**
   * Set the host entity of the entity list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $host
   *   The host entity.
   */
  public function setHost(EntityInterface $host);

  /**
   * Get the host entity of the entity list.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Return the host entity.
   */
  public function getHost();

}
