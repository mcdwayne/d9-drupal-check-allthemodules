<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface;
use Drupal\commerce_klarna_payments\Klarna\ObjectNormalizer;
use Webmozart\Assert\Assert;

/**
 * Value object for addresses.
 */
class Address implements AddressInterface {

  use ObjectNormalizer;

  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title) : AddressInterface {
    $this->data['title'] = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGivenName(string $name) : AddressInterface {
    $this->data['given_name'] = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFamilyName(string $name) : AddressInterface {
    $this->data['family_name'] = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail(string $mail) : AddressInterface {
    $this->data['email'] = $mail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreetAddress(string $address) : AddressInterface {
    $this->data['street_address'] = $address;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreetAddress2(string $address) : AddressInterface {
    $this->data['street_address2'] = $address;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode(string $code) : AddressInterface {
    $this->data['postal_code'] = $code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCity(string $city) : AddressInterface {
    $this->data['city'] = $city;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegion(string $region) : AddressInterface {
    $this->data['region'] = $region;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhone(string $number) : AddressInterface {
    $this->data['phone'] = $number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountry(string $country) : AddressInterface {
    Assert::length($country, 2);

    $this->data['country'] = $country;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrganization(string $organization) : AddressInterface {
    $this->data['organization_name'] = $organization;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttention(string $attention) : AddressInterface {
    $this->data['attention'] = $attention;
    return $this;
  }

}
