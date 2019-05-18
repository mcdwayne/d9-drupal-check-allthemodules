<?php

namespace Drupal\alexanders\Entity;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface AlexandersOrderItemInterface.
 *
 * @package Drupal\alexanders\Entity
 */
interface AlexandersOrderItemInterface extends ContentEntityInterface {

  /**
   * Get SKU of product in order item.
   *
   * @return string
   *   Product stock keeping unit.
   */
  public function getSku();

  /**
   * Set SKU of order item.
   *
   * @param string $sku
   *   Stock keeping unit.
   *
   * @return $this
   */
  public function setSku($sku);

  /**
   * Get QTY of order items printed by Alexanders.
   *
   * @return int
   *   # of items.
   */
  public function getQuantity();

  /**
   * Set number of times Alexanders should print this item.
   *
   * @param int $qty
   *   QTY of item that should be printed.
   *
   * @return $this
   */
  public function setQuantity($qty);

  /**
   * Get primary file associated with item.
   *
   * @return string
   *   URL for the order item.
   */
  public function getFile();

  /**
   * Set primary file URL.
   *
   * @param string $url
   *   Public URL for the image/pdf file.
   *
   * @return $this
   */
  public function setFile($url);

  /**
   * Get secondary file for item (guts or foil depending on product).
   *
   * @return string
   *   URL for the order item.
   */
  public function getAddFile();

  /**
   * Set secondary file URL.
   *
   * @param string $url
   *   Public URL for the image/pdf file.
   *
   * @return $this
   */
  public function setAddFile($url);

  /**
   * Returns width of order item in inches.
   *
   * @return int
   */
  public function getWidth();

  /**
   * Set order item width.
   *
   * @param int $width
   *   Width, in inches.
   *
   * @return $this
   */
  public function setWidth($width);

  /**
   * Returns height of order item in inches.
   *
   * @return int
   */
  public function getHeight();

  /**
   * Set order item height.
   *
   * @param int $height
   *   Height, in inches.
   *
   * @return $this
   */
  public function setHeight($height);

  /**
   * Get folds on order item.
   *
   * @return string
   */
  public function getFolds();

  /**
   * Set folds on item.
   *
   * @param string $folds
   *   Folds for the order item.
   *
   * @return $this
   */
  public function setFolds($folds);

  /**
   * Whether the item is a variable item (i.e. customized).
   *
   * @return bool
   */
  public function isVariable();

  /**
   * Set order item's variable status.
   *
   * @param bool $variable
   *   Variable status.
   *
   * @return $this
   */
  public function setVariable($variable);

  /**
   * Whether the item is a duplex item (i.e. double sided vs. single sided).
   *
   * @return bool
   */
  public function isDuplex();

  /**
   * Set order item's duplex status.
   *
   * @param bool $duplex
   *   Duplex status.
   *
   * @return $this
   */
  public function setDuplex($duplex);

  /**
   * Get media type associated with this order item.
   *
   * @return string
   */
  public function getMedia();

  /**
   * Set media type.
   *
   * @param string $media
   *   Media type for this order item.
   *
   * @return $this
   */
  public function setMedia($media);

}
