<?php

namespace Drupal\cbo_organization\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides helper functions.
 */
abstract class OrganizationTestBase extends WebTestBase {

  use OrganizationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['cbo_organization'];

  /**
   * An organization.
   *
   * @var \Drupal\cbo_organization\OrganizationInterface
   */
  protected $organization;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->organization = $this->createOrganization();
  }

}
