<?php

namespace Drupal\owms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining OWMS Data entities.
 */
interface OwmsDataInterface extends ConfigEntityInterface {

  /**
   * Gets the endpoint.

   * @return string
   */
  public function getEndpointIdentifier();

  /**
   * Checks if the Configuration Entity validates.
   *
   * @return bool|\Exception[]
   *   Returns an array of exceptions keyed by the entity property.
   */
  public function validate();

  /**
   * Convert endpoint identifier into an actual Endpoint.
   *
   * @return string
   */
  public function getEndpointUrl();

  /**
   * Gets the OwmsManager instance.
   *
   * @return \Drupal\owms\OwmsManagerInterface
   */
  public function getOwmsManagerInstance();

  /**
   * Gets the XML belonging to the endpoint.
   *
   * Use the stored value before targeting the endpoint.
   *
   * @return \SimpleXMLElement
   */
  public function getXml();

  /**
   * Returns an array of items saved.
   *
   * @return array
   */
  public function getItems();

  /**
   * Returns an array keyed by the identifier of items that are not deprecated.
   */
  public function getValidItems();

  /**
   * Returns an array of items that are no longer used.
   *
   * @return array
   */
  public function getDeprecatedItems();
}
