<?php

namespace Drupal\gopay\Contact;

/**
 * Interface ContactInterface.
 *
 * @package Drupal\gopay\Contact
 */
interface ContactInterface {

  /**
   * Sets first name.
   *
   * @param string $first_name
   *   First name.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setFirstName($first_name);

  /**
   * Sets last name.
   *
   * @param string $last_name
   *   Last name.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setLastName($last_name);

  /**
   * Sets email.
   *
   * @param string $email
   *   Email.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setEmail($email);

  /**
   * Sets phone number.
   *
   * @param string $phone_number
   *   Phone number.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setPhoneNumber($phone_number);

  /**
   * Sets city.
   *
   * @param string $city
   *   City.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setCity($city);

  /**
   * Sets street.
   *
   * @param string $street
   *   Street.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setStreet($street);

  /**
   * Sets postal code.
   *
   * @param string $postal_code
   *   Postal code.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   */
  public function setPostalCode($postal_code);

  /**
   * Sets country code.
   *
   * @param string $country_code
   *   Country code compliant with  ISO 3166-1 alpha-3.
   *
   * @return \Drupal\gopay\Contact\ContactInterface
   *   Returns itself.
   *
   * @see https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
   */
  public function setCountryCode($country_code);

  /**
   * Creates contact configuration compatible with GoPay SDK Payments.
   *
   * @see https://doc.gopay.com/en/#contact
   *
   * @return array
   *   Configuration of this contact
   */
  public function toArray();

}
