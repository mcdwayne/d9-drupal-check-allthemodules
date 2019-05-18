<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\CustomerInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrganizationEntityTypeInterface;
use Drupal\commerce_klarna_payments\Klarna\ObjectNormalizer;
use Webmozart\Assert\Assert;

/**
 * Value object for customers.
 */
class Customer implements CustomerInterface {

  use ObjectNormalizer;

  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setBirthday(\DateTime $dateTime) : CustomerInterface {
    $this->data['date_of_birth'] = $dateTime->format('Y-m-d');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGender(string $gender) : CustomerInterface {
    $this->data['gender'] = $gender;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastFourSsn(string $ssn) : CustomerInterface {
    $this->data['last_four_ssn'] = $ssn;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSsn(string $ssn) : CustomerInterface {
    $this->data['national_identification_number'] = $ssn;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setType(string $type) : CustomerInterface {
    $this->data['type'] = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setVatId(string $vatId) : CustomerInterface {
    $this->data['vat_id'] = $vatId;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrganizationRegistrationId(string $id) : CustomerInterface {
    $this->data['organization_registration_id'] = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrganizationEntityType(string $type) : CustomerInterface {
    $interface = new \ReflectionClass(OrganizationEntityTypeInterface::class);

    Assert::oneOf($type, $interface->getConstants());
    $this->data['organization_entity_type'] = $type;

    return $this;
  }

}
