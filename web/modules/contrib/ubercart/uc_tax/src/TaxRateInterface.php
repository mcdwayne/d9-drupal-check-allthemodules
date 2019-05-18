<?php

namespace Drupal\uc_tax;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines a interface for a tax rate configuration entity.
 */
interface TaxRateInterface extends ConfigEntityInterface {

  /**
   * Sets the tax rate ID.
   *
   * @param string $id
   *   The tax rate ID.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\uc_tax\TaxRatePluginInterface
   *   The plugin instance for this tax rate.
   */
  public function getPlugin();

  /**
   * The tax rate label.
   *
   * @return string
   *   The tax rate label.
   */
  public function getLabel();

  /**
   * The tax rate label.
   *
   * @param string $label
   *   The tax rate label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Product item types subject to this tax rate.
   *
   * @return array
   *   Product item types subject to this tax rate.
   */
  public function getProductTypes();

  /**
   * Product item types subject to this tax rate.
   *
   * @param array $product_types
   *   Product item types subject to this tax rate.
   *
   * @return $this
   */
  public function setProductTypes(array $product_types);

  /**
   * Line item types subject to this tax rate.
   *
   * @return array
   *   Line item types subject to this tax rate.
   */
  public function getLineItemTypes();

  /**
   * Line item types subject to this tax rate.
   *
   * @param array $line_item_types
   *   Line item types subject to this tax rate.
   *
   * @return $this
   */
  public function setLineItemTypes(array $line_item_types);

  /**
   * The tax rate weight.
   *
   * @return int
   *   The tax rate weight.
   */
  public function getWeight();

  /**
   * The tax rate weight.
   *
   * @param int $weight
   *   The tax rate weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Whether to display prices including tax.
   *
   * @return bool
   *   TRUE if display prices include tax.
   */
  public function isIncludedInPrice();

  /**
   * Whether to display prices including tax.
   *
   * @param bool $included
   *   Whether to display prices including tax.
   *
   * @return $this
   */
  public function setIncludedInPrice($included);

  /**
   * The text to display next to prices if tax is included.
   *
   * @return string
   *   The text to display next to prices if tax is included.
   */
  public function getInclusionText();

  /**
   * The text to display next to prices if tax is included.
   *
   * @param string $inclusion_text
   *   The text to display next to prices if tax is included.
   *
   * @return $this
   */
  public function setInclusionText($inclusion_text);

  /**
   * If the tax applies only to shippable products.
   *
   * @return bool
   *   TRUE if the tax applies only to shippable products.
   */
  public function isForShippable();

  /**
   * If the tax applies only to shippable products.
   *
   * @param bool $shippable
   *   If the tax applies only to shippable products.
   *
   * @return $this
   */
  public function setForShippable($shippable);

}
