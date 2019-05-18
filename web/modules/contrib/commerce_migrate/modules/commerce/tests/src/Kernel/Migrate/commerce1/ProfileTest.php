<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\profile\Entity\Profile;

/**
 * Tests billing profile migration.
 *
 * @requires module address
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProfileTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'migrate_plus',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateProfiles();
  }

  /**
   * Test profile migration from Drupal 7 Commerce to Drupal 8.
   */
  public function testProfile() {
    // @todo. Decide if the modification of the changed time for billing profile
    // needs to be investigated.
    $this->assertProfile(1, 'customer', '4', 'und', TRUE, TRUE, '1493287440', NULL);
    $this->assertProfile(2, 'shipping', '4', 'und', TRUE, TRUE, '1493287445', '1493287450');
    $this->assertProfile(3, 'shipping', '4', 'und', TRUE, FALSE, '1493287450', '1493287455');

    $this->assertProfileRevision(4, 'customer', '4', 'und', TRUE, FALSE, '1508452606', NULL);

    $profile = Profile::load(1);
    $address = $profile->get('address')->first()->getValue();
    $this->assertAddressField($address, 'US', 'CA', 'Visalia', NULL, '93277-8329', '', '16 Hampton Ct', NULL, 'Sample', NULL, 'Customer', NULL);

    $profile = Profile::load(4);
    $address = $profile->get('address')->first()->getValue();
    $this->assertAddressField($address, 'NZ', '', 'Visalia', '', '93277-8329', '', '16 Hampton Ct', '', 'Sample', NULL, 'Customer', NULL);
  }

}
