<?php

namespace Drupal\upgrade_tool\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Upgrade log entities.
 *
 * @ingroup upgrade_tool
 */
interface UpgradeLogInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Upgrade log name.
   *
   * @return string
   *   Name of the Upgrade log.
   */
  public function getName();

  /**
   * Sets the Upgrade log name.
   *
   * @param string $name
   *   The Upgrade log name.
   *
   * @return \Drupal\upgrade_tool\Entity\UpgradeLogInterface
   *   The called Upgrade log entity.
   */
  public function setName($name);

  /**
   * Gets the Upgrade log config path.
   *
   * @return string
   *   Config path of the Upgrade log.
   */
  public function getConfigPath();

  /**
   * Sets the Upgrade log config path.
   *
   * @param string $path
   *   The Upgrade log config path.
   *
   * @return \Drupal\upgrade_tool\Entity\UpgradeLogInterface
   *   The called Upgrade log entity.
   */
  public function setConfigPath($path);

  /**
   * Gets the Upgrade log config property.
   *
   * @return string
   *   Config property of the Upgrade log.
   */
  public function getConfigProperty();

  /**
   * Sets the Upgrade log config property.
   *
   * @param string $property
   *   The Upgrade log config property.
   *
   * @return \Drupal\upgrade_tool\Entity\UpgradeLogInterface
   *   The called Upgrade log entity.
   */
  public function setConfigProperty($property);

  /**
   * Gets the Upgrade log creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Upgrade log.
   */
  public function getCreatedTime();

  /**
   * Sets the Upgrade log creation timestamp.
   *
   * @param int $timestamp
   *   The Upgrade log creation timestamp.
   *
   * @return \Drupal\upgrade_tool\Entity\UpgradeLogInterface
   *   The called Upgrade log entity.
   */
  public function setCreatedTime($timestamp);

}
