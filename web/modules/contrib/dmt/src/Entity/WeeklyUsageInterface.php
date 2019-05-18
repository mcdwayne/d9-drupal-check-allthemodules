<?php

namespace Drupal\dmt\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Weekly usage entities.
 *
 * @ingroup dmt
 */
interface WeeklyUsageInterface extends ContentEntityInterface {

  /**
   * Gets the Weekly usage module.
   *
   * @return \Drupal\dmt\Entity\ModuleInterface
   *   Module of the Weekly usage.
   */
  public function getModule();

  /**
   * Sets the Weekly usage module.
   *
   * @param \Drupal\dmt\Entity\ModuleInterface $module
   *   The Weekly usage module.
   *
   * @return \Drupal\dmt\Entity\WeeklyUsageInterface
   *   The called Weekly usage entity.
   */
  public function setModule($module);

  /**
   * Gets the install count.
   *
   * @return int
   *   Weekly install count.
   */
  public function getInstallCount();

  /**
   * Sets the install count.
   *
   * @param int $count
   *   Weekly install count.
   *
   * @return \Drupal\dmt\Entity\WeeklyUsageInterface
   *   The called Weekly usage entity.
   */
  public function setInstallCount($count);

  /**
   * Gets the week timestamp.
   *
   * @return Drupal\Core\TypedData\Type\DateTimeInterface
   *   Date of the weekly usage.
   */
  public function getDate();

  /**
   * Sets the week timestamp.
   *
   * @param Drupal\Core\TypedData\Type\DateTimeInterface $date
   *   Date of the weekly usage.
   *
   * @return \Drupal\dmt\Entity\WeeklyUsageInterface
   *   The called Weekly usage entity.
   */
  public function setDate($date);

}
