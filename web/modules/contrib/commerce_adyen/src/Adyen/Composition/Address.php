<?php

namespace Drupal\commerce_adyen\Adyen\Composition;

/**
 * Address for OpenInvoice payment.
 */
class Address {

  const BILLING = 'billing';
  const DELIVERY = 'delivery';

  /**
   * Address type. Can be "billing" or "delivery".
   *
   * @var string
   */
  private $type = '';
  /**
   * City.
   *
   * @var string
   */
  private $city = '';
  /**
   * Street.
   *
   * @var string
   */
  private $street = '';
  /**
   * ISO 3166-1 Alpha 2 country code.
   *
   * @var string
   *
   * @link https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
   */
  private $country = '';
  /**
   * Postal code.
   *
   * @var string
   */
  private $postalCode = '';
  /**
   * State or province name.
   *
   * @var string
   */
  private $stateOrProvince = '';
  /**
   * Name or number of building.
   *
   * @var string
   */
  private $houseNumberOrName = '';

  /**
   * Address constructor.
   *
   * @param string $type
   *   Address type. Use one of constants of this class.
   */
  public function __construct($type) {
    $this->setType($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    if (!in_array($type, [static::BILLING, static::DELIVERY])) {
      throw new \InvalidArgumentException('Adyen address type is incorrect!');
    }

    $this->type = $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getCity() {
    return $this->city;
  }

  /**
   * {@inheritdoc}
   */
  public function setCity($city) {
    $this->city = $city;
  }

  /**
   * {@inheritdoc}
   */
  public function getStreet() {
    return $this->street;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreet($street) {
    $this->street = $street;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry() {
    return $this->country;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountry($country) {
    $this->country = $country;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCode() {
    return $this->postalCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode($postal_code) {
    $this->postalCode = $postal_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateOrProvince() {
    return $this->stateOrProvince;
  }

  /**
   * {@inheritdoc}
   */
  public function setStateOrProvince($state_or_province) {
    $this->stateOrProvince = $state_or_province;
  }

  /**
   * {@inheritdoc}
   */
  public function getHouseNumberOrName() {
    return $this->houseNumberOrName;
  }

  /**
   * {@inheritdoc}
   */
  public function setHouseNumberOrName($house_number_or_name) {
    $this->houseNumberOrName = $house_number_or_name;
  }

}
