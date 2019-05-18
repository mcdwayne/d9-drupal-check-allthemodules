<?php

namespace Drupal\Tests\agreement\Kernel\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\user\Entity\User;

/**
 * Tests agreement migration from 6.x-2.x.
 *
 * @group agreement
 */
class AgreementMigrateTest extends MigrateDrupal6TestBase {

  public static $modules = ['system', 'node', 'user', 'filter', 'agreement'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $agreementFixture = __DIR__ . '/../../../fixtures/drupal6.php';
    $this->assertNotFalse(realpath($agreementFixture));
    $this->loadFixture($agreementFixture);

    $this->installEntitySchema('user_role');
    $this->installEntitySchema('filter_format');
    $this->installEntitySchema('agreement');
    $this->installSchema('agreement', ['agreement']);
    $this->installConfig('agreement');

    $migrations = [
      'd6_filter_format',
      'd6_user_role',
      'd6_user',
      'd6_agreement',
    ];

    $this->executeMigrations($migrations);
  }

  /**
   * Asserts that agreement types and agreements migrated.
   */
  public function testDataMigration() {
    $agreementHandler = $this->container->get('agreement.handler');
    $entityTypeManager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $configStorage */
    $configStorage = $entityTypeManager->getStorage('agreement');
    $agreement_types = $configStorage->loadMultiple();

    $this->assertEquals(1, count($agreement_types), 'Found default agreement type.');

    /** @var \Drupal\agreement\Entity\Agreement $default */
    $default = $configStorage->load('default');

    $user2 = User::load(2);
    $user8 = User::load(8);

    $this->assertGreaterThan(-1, $agreementHandler->lastAgreed($default, $user2), 'john.doe agreed to default agreement.');
    $this->assertEquals(-1, $agreementHandler->lastAgreed($default, $user8), 'john.roe did not agree to default agreement.');
  }

}
