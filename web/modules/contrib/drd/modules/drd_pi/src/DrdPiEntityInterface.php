<?php

namespace Drupal\drd_pi;

use Drupal\drd\Entity\BaseInterface;

/**
 * Provides an interface for platform based entities.
 */
interface DrdPiEntityInterface {

  /**
   * ID of the DrdPiEntity.
   *
   * @return string
   *   The ID.
   */
  public function id();

  /**
   * Label of the DrdPiEntity.
   *
   * @return string
   *   The label.
   */
  public function label();

  /**
   * The host this entity is attached to.
   *
   * @return DrdPiHost
   *   The host entity.
   */
  public function host();

  /**
   * Set the matching Drd entity.
   *
   * @param \Drupal\drd\Entity\BaseInterface $entity
   *   The DRD entity.
   *
   * @return $this
   */
  public function setDrdEntity(BaseInterface $entity);

  /**
   * Get the matching DRD entity.
   *
   * @return \Drupal\drd\Entity\BaseInterface
   *   The DRD entity.
   */
  public function getDrdEntity();

  /**
   * Check if the DrdPiEntity has a matching DRD entity.
   *
   * @return bool
   *   TRUE if it has a matching DRD entity, FALSE otherwise.
   */
  public function hasDrdEntity();

  /**
   * Create the matching DRD entity.
   *
   * @return $this
   */
  public function create();

  /**
   * Update the matching DRD entity.
   *
   * @return $this
   */
  public function update();

  /**
   * Set a header key/value pair.
   *
   * @param string $key
   *   The key.
   * @param string $value
   *   The value.
   *
   * @return $this
   */
  public function setHeader($key, $value);

}
