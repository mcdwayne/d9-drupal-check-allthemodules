<?php

namespace Drupal\Tests\crm_core_user_sync\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\crm_core_user_sync\CrmCoreUserSyncRelationRules;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Test CrmCoreUserSyncRelationRules service.
 *
 * @property \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationRules rulesService
 * @group crm_core_user_sync
 * @coversDefaultClass \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationRules
 */
class CrmCoreUserSyncRelationRulesTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $rules = [];
    $rules[] = [
      'role' => 'customer',
      'contact_type' => 'customer',
      'enabled' => TRUE,
      'weight' => 1,
    ];

    $rules[] = [
      'role' => 'authenticated',
      'contact_type' => 'individual',
      'enabled' => TRUE,
      'weight' => 10,
    ];

    $config = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config
      ->expects($this->once())
      ->method('get')
      ->with('rules')
      ->willReturn($rules);

    $config_name = 'crm_core_user_sync.settings';
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->expects($this->once())
      ->method('get')
      ->with($config_name)
      ->willReturn($config);

    $this->rulesService = new CrmCoreUserSyncRelationRules($configFactory, $config_name);
  }

  /**
   * Tests CrmCoreUserSyncRelationRules service.
   */
  public function testCrmCoreUserSyncRelationRulesService() {
    $account_authenticated = $this->createMock(UserInterface::class);
    $account_authenticated
      ->expects($this->any())
      ->method('hasRole')
      ->willReturnMap([['authenticated', TRUE], ['customer', FALSE]]);

    $account_customer = $this->createMock(UserInterface::class);
    $account_customer
      ->expects($this->any())
      ->method('hasRole')
      ->willReturnMap([['authenticated', FALSE], ['customer', TRUE]]);

    $contact_individual = $this->createMock(IndividualInterface::class);
    $contact_individual
      ->expects($this->any())
      ->method('bundle')
      ->willReturn('individual');

    $contact_customer = $this->createMock(IndividualInterface::class);
    $contact_customer
      ->expects($this->any())
      ->method('bundle')
      ->willReturn('customer');

    $this->assertFalse($this->rulesService->valid($account_customer, $contact_individual), 'Individual contact cannot be related to customer user.');
    $this->assertFalse($this->rulesService->valid($account_authenticated, $contact_customer), 'Customer contact can be related to authenticated user.');
    $this->assertTrue($this->rulesService->valid($account_authenticated, $contact_individual), 'Individual contact can be related to authenticated user.');
    $this->assertTrue($this->rulesService->valid($account_customer, $contact_customer), 'Customer contact can be related to customer user.');
  }

}
