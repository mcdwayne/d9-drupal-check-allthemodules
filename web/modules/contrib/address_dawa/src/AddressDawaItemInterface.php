<?php

namespace Drupal\address_dawa;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for DAWA address.
 */
interface AddressDawaItemInterface extends FieldItemInterface {

  /**
   * Get address type.
   *
   * @return string
   *   Address type.
   */
  public function getType();

  /**
   * Get address UUID.
   *
   * @return string
   *   UUID.
   */
  public function getId();

  /**
   * Get address status.
   *
   * @return string
   *   Address status.
   */
  public function getStatus();

  /**
   * Get address textual representation.
   *
   * @return string
   *   Address text.
   */
  public function getTextValue();

  /**
   * Get address latitude coordinate.
   *
   * @return string
   *   Latitude geo-coordinate.
   */
  public function getLat();

  /**
   * Get address longitude coordinate.
   *
   * @return string
   *   Longitude geo-coordinate.
   */
  public function getLng();

  /**
   * Get raw DAWA address data.
   *
   * @return array
   *   Data.
   */
  public function getData();

}
