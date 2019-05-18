<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\profile\Entity\ProfileType;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests profile type migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class ProfileTypeTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'action',
    'address',
    'address',
    'commerce',
    'commerce',
    'commerce_migrate',
    'commerce_migrate_magento',
    'commerce_order',
    'commerce_price',
    'commerce_store',
    'entity',
    'entity_reference_revisions',
    'field',
    'inline_entity_form',
    'migrate_plus',
    'options',
    'profile',
    'profile',
    'state_machine',
    'system',
    'telephone',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/magento2_customer_address_20180618_003449.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installConfig(['system']);

    $this->executeMigrations([
      'magento2_user',
      'magento2_profile_type',
    ]);
  }

  /**
   * Tests node type migration.
   */
  public function testProfileType() {
    $profile_type = ProfileType::load('customer');
    $this->assertInstanceOf(ProfileType::class, $profile_type);
    $this->assertSame('en', $profile_type->language()->getId());

    $profile_type = ProfileType::load('shipping');
    $this->assertInstanceOf(ProfileType::class, $profile_type);
    $this->assertSame('en', $profile_type->language()->getId());
  }

}
