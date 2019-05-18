<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;
use Drupal\user\Entity\User;

/**
 * Tests user migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class UserTest extends CsvTestBase {

  use CommerceMigrateCoreTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_migrate',
    'commerce_migrate_magento',
    'migrate_plus',
    'system',
    'user',
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
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installConfig(['system']);
    $this->executeMigration('magento2_user');
  }

  /**
   * Test attribute migration.
   */
  public function testUser() {
    $this->assertUserEntity(1, 'Veronica Costello', 'roni_cost@example.com', NULL, '0', '', 'en', '', NULL, ['authenticated']);
    $this->assertUserEntity(2, 'Tui Song', 'tui@example.com', NULL, '0', '', 'en', '', NULL, ['authenticated']);
    /** @var \Drupal\user\UserInterface $user */
    $this->assertNull(User::load(3));
  }

}
