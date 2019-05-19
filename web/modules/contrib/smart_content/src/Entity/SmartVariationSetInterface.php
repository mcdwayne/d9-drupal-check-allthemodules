<?php

namespace Drupal\smart_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\smart_content\Variation\VariationInterface;
use Drupal\smart_content\VariationSetType\VariationSetTypeInterface;

/**
 * Provides an interface for defining Smart variation set entities.
 */
interface SmartVariationSetInterface extends ConfigEntityInterface {

  /**
   * Gets the wrapper object for this entity.
   *
   * @return \Drupal\smart_content\VariationSetType\VariationSetTypeInterface|null
   */
  public function getVariationSetType();

  /**
   * Sets a reference to the wrapper object for this entity.
   *
   * @param \Drupal\smart_content\VariationSetType\VariationSetTypeInterface $variation_set_type
   */
  public function setVariationSetType(VariationSetTypeInterface $variation_set_type);

  /**
   * Adds a variation to this variation set.
   *
   * @param \Drupal\smart_content\Variation\VariationInterface $variation
   */
  public function addVariation(VariationInterface $variation);

  /**
   * Gets the variations that are a part of this variation set.
   *
   * @return \Drupal\smart_content\Variation\VariationInterface[]
   */
  public function getVariations();

  /**
   * Gets the specified variation from this variation set.
   *
   * @param $id
   *
   * @return \Drupal\smart_content\Variation\VariationInterface
   */
  public function getVariation($id);

  /**
   * Removes the specified variation from this variation set.
   *
   * @param $id
   */
  public function removeVariation($id);

}
