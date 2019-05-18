<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to list allowed organization entity types.
 */
interface OrganizationEntityTypeInterface {

  public const TYPE_LIMITED_COMPANY = 'LIMITED_COMPANY';
  public const TYPE_PUBLIC_LIMITED_COMPANY = 'PUBLIC_LIMITED_COMPANY';
  public const TYPE_ENTREPRENEURIAL_COMPANY = 'ENTREPRENEURIAL_COMPANY';
  public const TYPE_LIMITED_PARTNERSHIP_LIMITED_COMPANY = 'LIMITED_PARTNERSHIP_LIMITED_COMPANY';
  public const TYPE_LIMITED_PARTNERSHIP = 'LIMITED_PARTNERSHIP';
  public const TYPE_GENERAL_PARTNERSHIP = 'GENERAL_PARTNERSHIP';
  public const TYPE_REGISTERED_SOLE_TRADER = 'REGISTERED_SOLE_TRADER';
  public const TYPE_SOLE_TRADER = 'SOLE_TRADER';
  public const TYPE_CIVIL_LAW_PARTNERSHIPA = 'CIVIL_LAW_PARTNERSHIP';
  public const TYPE_PUBLIC_INSTITUTION = 'PUBLIC_INSTITUTION';
  public const TYPE_OTHER = 'OTHER';

}
