<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

use Drupal\commerce\BundlePluginInterface;
use Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface;

/**
 * Defines an interface for Inventory Adjustment type plugins.
 */
interface InventoryAdjustmentTypeInterface extends BundlePluginInterface {

  /**
   * Gets the Adjustment Type label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The Adjustment Type label.
   */
  public function getLabel();

  /**
   * Gets the Adjustment Type preposition label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The Adjustment Type preposition label.
   */
  public function getPrepositionLabel();

  /**
   * Gets the Adjustment Type related preposition label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The Adjustment Type related preposition label.
   */
  public function getRelatedPrepositionLabel();

  /**
   * Gets the Adjustment Type untranslated sentence template.
   *
   * Possible used contextual options:
   *   - "%item": The Inventory Item label.
   *   - "%location": The translated Location name.
   *   - "%related_location": The translated RelatedLocation name.
   * Possible used Adjustment Type options:
   *   - "@adjustment_verb": The adjustment verb.
   *   - "@adjustment_preposition": The adjustment preposition.
   *   - "@related_preposition": The related adjustment preposition, if
   *     adjustment type has a related adjustment.
   *
   * @return string
   *   The Adjustment Type untranslated sentence template.
   */
  public function getSentenceLabelTemplate();

  /**
   * Get a label replacement array based on the passed-in adjustment.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $adjustment
   *   The adjustment to fill in the definition.
   * @param bool $link_entities
   *   Whether the replacements should be linked to their respective entities.
   *
   * @return array
   *   The adjustment sentence replacements.
   */
  public function getSentenceLabelReplacements(InventoryAdjustmentInterface $adjustment, $link_entities = FALSE);

  /**
   * Get a filled sentence label based on the passed-in adjustment.
   *
   * @param \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $adjustment
   *   The adjustment to fill in the definition.
   * @param bool $link_entities
   *   Whether the replacements should be linked to their respective entities.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The adjustment sentence label.
   */
  public function getSentenceLabel(InventoryAdjustmentInterface $adjustment, $link_entities = FALSE);

  /**
   * Gets the Adjustment Type verb label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The Adjustment Type verb label.
   */
  public function getVerbLabel();

  /**
   * Whether this Adjustment Type has a related Adjustment type.
   *
   * @return bool
   *   True if this Adjustment Type has a related Adjustment Type. False
   *   otherwise.
   */
  public function hasRelatedAdjustmentType();

  /**
   * Gets the related Adjustment Type ID.
   *
   * @return string
   *   The related Adjustment Type ID
   */
  public function getRelatedAdjustmentTypeId();

  /**
   * Gets the related Adjustment Type.
   *
   * @return \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface|null
   *   The related Adjustment Type if set. Null otherwise.
   */
  public function getRelatedAdjustmentType();

  /**
   * Adjust a quantity of inventory.
   *
   * @param float $quantity
   *   The current quantity of inventory to adjust.
   *
   * @return float
   *   The adjusted inventory. Positive if being increased. Negative if being
   *   decreased.
   */
  public function adjustQuantity($quantity);

}
