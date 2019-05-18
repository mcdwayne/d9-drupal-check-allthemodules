<?php

namespace Drupal\gopay\Contact;

/**
 * Class Contact.
 *
 * @package Drupal\gopay\Contact
 */
class Contact implements ContactInterface {

  /**
   * First name.
   *
   * @var string
   */
  protected $firstName;

  /**
   * Last name.
   *
   * @var string
   */
  protected $lastName;

  /**
   * Email.
   *
   * @var string
   */
  protected $email;

  /**
   * Phone number.
   *
   * @var string
   */
  protected $phoneNumber;

  /**
   * City.
   *
   * @var string
   */
  protected $city;

  /**
   * Street.
   *
   * @var string
   */
  protected $street;

  /**
   * Postal code.
   *
   * @var string
   */
  protected $postalCode;

  /**
   * Country code.
   *
   * @var string
   */
  protected $countryCode;

  /**
   * {@inheritdoc}
   */
  public function setFirstName($first_name) {
    $this->firstName = $first_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($last_name) {
    $this->lastName = $last_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhoneNumber($phone_number) {
    $this->phoneNumber = $phone_number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCity($city) {
    $this->city = $city;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreet($street) {
    $this->street = $street;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode($postal_code) {
    $this->postalCode = $postal_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountryCode($country_code) {
    $this->countryCode = $country_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    return [
      'first_name' => $this->firstName,
      'last_name' => $this->lastName,
      'email' => $this->email,
      'phone_number' => $this->phoneNumber,
      'city' => $this->city,
      'street' => $this->street,
      'postal_code' => $this->postalCode,
      'country_code' => $this->countryCode,
    ];
  }

}
