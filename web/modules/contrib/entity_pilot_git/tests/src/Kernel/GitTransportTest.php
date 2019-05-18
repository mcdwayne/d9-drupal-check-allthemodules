<?php

namespace Drupal\Tests\entity_pilot_git\Kernel;

use Drupal\entity_pilot\Entity\Account;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test for \Drupal\entity_pilot_git\GitTransport.
 *
 * @group entity_pilot_git
 */
class GitTransportTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_git',
    'entity_pilot',
    'entity_pilot_git_test_config',
    'serialization',
    'rest',
    'hal',
    'node',
    'user',
    'system',
    'field',
  ];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installConfig(['entity_pilot_git']);
    $this->installConfig(['entity_pilot_git_test_config']);
  }

  /**
   * Tests the getFlight method.
   */
  public function testGetFlight() {
    $flight_id = 1479356059;
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = $this->container->get('config.factory');
    $config->getEditable('entity_pilot_git.settings')
      ->set('export_directory', drupal_get_path('module', 'entity_pilot_git') . '/tests/test_content')
      ->save();

    /** @var \Drupal\entity_pilot\TransportInterface $transport */
    $transport = $this->container->get('entity_pilot.transport');
    $flight = $transport->getFlight($flight_id, Account::load('test_account'));

    $expected_uuids = [
      '25d26768-0fe9-424f-a747-125cf177b7ce',
      '4503be64-d080-4e95-a255-1e37e1a6022f',
      'f4e42bae-a611-428f-ae4e-1e7ebd35110b',
      '8ccf3693-6a33-498d-b45f-a04e070e89c9',
      '35fbc0b0-f38d-4eea-ae2f-4d6a49f1d9f9',
    ];
    $this->assertEquals($expected_uuids, array_keys($flight->getContents()));

  }

}
