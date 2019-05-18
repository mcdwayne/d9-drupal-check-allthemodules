<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to describe order addresses.
 */
interface AddressInterface extends ObjectInterface {

  /**
   * Sets the title.
   *
   * @param string $title
   *   The title.
   *
   * @return $this
   *   The self.
   */
  public function setTitle(string $title) : AddressInterface;

  /**
   * Sets the given name.
   *
   * @param string $name
   *   The name.
   *
   * @return $this
   *   The self.
   */
  public function setGivenName(string $name) : AddressInterface;

  /**
   * Sets the family name.
   *
   * @param string $name
   *   The family name.
   *
   * @return $this
   *   The self.
   */
  public function setFamilyName(string $name) : AddressInterface;

  /**
   * Sets the mail.
   *
   * @param string $mail
   *   The mail.
   *
   * @return $this
   *   The self.
   */
  public function setEmail(string $mail) : AddressInterface;

  /**
   * Sets the address.
   *
   * @param string $address
   *   The address.
   *
   * @return $this
   *   The self.
   */
  public function setStreetAddress(string $address) : AddressInterface;

  /**
   * Sets the address line 2.
   *
   * @param string $address
   *   The address.
   *
   * @return $this
   *   The self.
   */
  public function setStreetAddress2(string $address) : AddressInterface;

  /**
   * Sets the postal code.
   *
   * @param string $code
   *   The postal code.
   *
   * @return $this
   *   The self.
   */
  public function setPostalCode(string $code) : AddressInterface;

  /**
   * Sets the city.
   *
   * @param string $city
   *   The city.
   *
   * @return $this
   *   The self.
   */
  public function setCity(string $city) : AddressInterface;

  /**
   * Sets the region or state.
   *
   * @param string $region
   *   The region.
   *
   * @return $this
   *   The self.
   */
  public function setRegion(string $region) : AddressInterface;

  /**
   * Sets the phone number.
   *
   * @param string $number
   *   The phone number.
   *
   * @return $this
   *   The self.
   */
  public function setPhone(string $number) : AddressInterface;

  /**
   * Sets the ISO 3166 alpha-2 country.
   *
   * @param string $country
   *   The country.
   *
   * @return $this
   *   The self.
   */
  public function setCountry(string $country) : AddressInterface;

  /**
   * Sets the organization.
   *
   * @param string $organization
   *   The organization.
   *
   * @return $this
   *   The self.
   */
  public function setOrganization(string $organization) : AddressInterface;

  /**
   * Sets the attention.
   *
   * @param string $attention
   *   The attention.
   *
   * @return $this
   *   The self.
   */
  public function setAttention(string $attention) : AddressInterface;

}
