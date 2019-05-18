<?php

namespace Drupal\dcat\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Agent entities.
 *
 * @ingroup dcat
 */
interface DcatAgentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the Agent name.
   *
   * @return string
   *   Name of the Agent.
   */
  public function getName();

  /**
   * Sets the Agent name.
   *
   * @param string $name
   *   The Agent name.
   *
   * @return \Drupal\dcat\Entity\DcatAgentInterface
   *   The called Agent entity.
   */
  public function setName($name);

  /**
   * Gets the Agent creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Agent.
   */
  public function getCreatedTime();

  /**
   * Sets the Agent creation timestamp.
   *
   * @param int $timestamp
   *   The Agent creation timestamp.
   *
   * @return \Drupal\dcat\Entity\DcatAgentInterface
   *   The called Agent entity.
   */
  public function setCreatedTime($timestamp);

}
