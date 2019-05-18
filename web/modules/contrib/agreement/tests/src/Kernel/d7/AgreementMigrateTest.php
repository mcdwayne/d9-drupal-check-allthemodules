<?php

namespace Drupal\Tests\agreement\Kernel\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\user\Entity\User;

/**
 * Tests agreement migration from 7.x-2.x.
 *
 * @group agreement
 */
class AgreementMigrateTest extends MigrateDrupal7TestBase {

  public static $modules = ['system', 'node', 'user', 'filter', 'agreement'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $agreementFixture = __DIR__ . '/../../../fixtures/drupal7.php';
    $this->assertNotFalse(realpath($agreementFixture));
    $this->loadFixture($agreementFixture);

    $this->installEntitySchema('user_role');
    $this->installEntitySchema('filter_format');
    $this->installEntitySchema('agreement');
    $this->installSchema('agreement', ['agreement']);
    $this->installConfig('agreement');

    $migrations = [
      'd7_filter_format',
      'd7_user_role',
      'd7_user',
      'agreement_types',
      'd7_agreement',
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

    $this->assertEquals(2, count($agreement_types), 'Found two agreement types.');

    $expected_default_settings = [
      'roles' => ['authenticated'],
      'title' => 'Our agreement',
      'format' => 'filtered_html',
      'frequency' => 0,
      'success' => 'Thank you for accepting our agreement.',
      'failure' => 'You must accept our agreement to continue.',
      'revoked' => 'You successfully revoked your acceptance of our agreement',
      'checkbox' => 'I agree.',
      'submit' => 'Submit',
      'destination' => '',
      'visibility' => [
        'settings' => 0,
        'pages' => [],
      ],
      'recipient' => '',
      'reset_date' => 0,
    ];

    $expected_node1Agreement_settings = [
      'roles' => ['administrator'],
      'title' => 'Node 1 agreement',
      'format' => 'filtered_html',
      'frequency' => -1,
      'success' => 'Thank you for accepting our agreement.',
      'failure' => 'You must accept our agreement to continue.',
      'revoked' => 'You successfully revoked your acceptance of our agreement',
      'checkbox' => 'I agree to node 1',
      'submit' => 'Agree',
      'destination' => '/node/1',
      'visibility' => [
        'settings' => 1,
        'pages' => ['/node/1'],
      ],
      'recipient' => '',
      'reset_date' => 0,
    ];

    /** @var \Drupal\agreement\Entity\Agreement $default */
    $default = $configStorage->load('default');
    $this->assertEquals('Default agreement', $default->label());
    $this->assertEquals('Default agreement.', $default->get('agreement'));
    $this->assertEquals('/agreement', $default->get('path'));
    $this->assertEquals($expected_default_settings, $default->getSettings());

    /** @var \Drupal\agreement\Entity\Agreement $node1Agreement */
    $node1Agreement = $configStorage->load('node_1_agreement');
    $this->assertEquals('Node 1 agreement', $node1Agreement->label());
    $this->assertEquals('Agree to node 1.', $node1Agreement->get('agreement'));
    $this->assertEquals('/agree-to-node-1', $node1Agreement->get('path'));
    $this->assertEquals($expected_node1Agreement_settings, $node1Agreement->getSettings());

    $user2 = User::load(2);
    $user3 = User::load(3);

    $this->assertGreaterThan(-1, $agreementHandler->lastAgreed($default, $user2), 'Odo agreed to default agreement.');
    $this->assertEquals(-1, $agreementHandler->lastAgreed($default, $user3), 'Bob did not agree to default agreement.');

    $this->assertGreaterThan(-1, $agreementHandler->lastAgreed($node1Agreement, $user3), 'Bob agreed to node 1 agreement.');
    $this->assertEquals(-1, $agreementHandler->lastAgreed($node1Agreement, $user2), 'Odo did not agree to node 1 agreement.');
  }

}
