<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to describe customer.
 */
interface CustomerInterface extends ObjectInterface {

  /**
   * Sets the birthday.
   *
   * @param \DateTime $dateTime
   *   The datetime.
   *
   * @return $this
   *   The self.
   */
  public function setBirthday(\DateTime $dateTime) : CustomerInterface;

  /**
   * Sets the gender.
   *
   * @param string $gender
   *   The gender.
   *
   * @return $this
   *   The self.
   */
  public function setGender(string $gender) : CustomerInterface;

  /**
   * Last four digits for customer social security number.
   *
   * @param string $ssn
   *   The last four digits of SSN.
   *
   * @return $this
   *   The self.
   */
  public function setLastFourSsn(string $ssn) : CustomerInterface;

  /**
   * Sets the customer sosial security number.
   *
   * @param string $ssn
   *   The SSN.
   *
   * @return $this
   *   The self.
   */
  public function setSsn(string $ssn) : CustomerInterface;

  /**
   * Sets the type.
   *
   * @param string $type
   *   The type.
   *
   * @return $this
   *   The self.
   */
  public function setType(string $type) : CustomerInterface;

  /**
   * Sets the VAT id.
   *
   * @param string $vatId
   *   The VAT id.
   *
   * @return $this
   *   The self.
   */
  public function setVatId(string $vatId) : CustomerInterface;

  /**
   * Sets the organization registration id.
   *
   * @param string $id
   *   The registration id.
   *
   * @return $this
   *   The self.
   */
  public function setOrganizationRegistrationId(string $id) : CustomerInterface;

  /**
   * Sets the organization entity type.
   *
   * @param string $type
   *   The type.
   *
   * @return $this
   *   The self.
   */
  public function setOrganizationEntityType(string $type) : CustomerInterface;

}
