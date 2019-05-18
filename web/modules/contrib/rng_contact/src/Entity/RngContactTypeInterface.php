<?php

namespace Drupal\rng_contact\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for contact type entity.
 */
interface RngContactTypeInterface extends ConfigEntityInterface {

  /**
   * Get the email field to use for Courier email communications.
   *
   * @return string|NULL
   *   The email field to use for Courier email communications.
   */
  public function getCourierEmailField();

  /**
   * Set the email field to use for Courier email communications.
   *
   * @param string|NULL $courier_email_field
   *   The email field to use for Courier email communications.
   *
   * @return $this
   *   Return this contact type for chaining.
   */
  public function setCourierEmailField($courier_email_field);

}
