<?php

namespace Drupal\Tests\commerce_xero\Unit;

use Drupal\commerce_xero\StrategyAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the entity access handler for commerce xero strategies.
 *
 * @group commerce_xero
 */
class StrategyAccessControlHandlerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
  }

  /**
   * Asserts the checkAccess method functions.
   */
  public function testCheckAccessAllowed() {

    $container = new ContainerBuilder();

    $moduleProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleProphet->invokeAll(Argument::any(), Argument::any())->willReturn([AccessResult::allowed()]);
    $container->set('module_handler', $moduleProphet->reveal());

    \Drupal::setContainer($container);

    $language = new Language(['id' => 'en']);
    $definition = [
      'id' => 'commerce_xero_strategy',
    ];
    $configEntityType = new ConfigEntityType($definition);

    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface');
    $strategyProphet->id()->willReturn('test_strategy');
    $strategyProphet->language()->willReturn($language);
    $strategyProphet->uuid()->willReturn('');
    $strategyProphet->getEntityTypeId()->willReturn('commerce_xero_strategy');
    $strategy = $strategyProphet->reveal();

    $accountProphet = $this->prophesize('\Drupal\Core\Session\AccountInterface');
    $accountProphet->id()->willReturn(1);
    $account = $accountProphet->reveal();

    $handler = new StrategyAccessControlHandler($configEntityType);

    $this->assertTrue($handler->access($strategy, 'view', $account));
  }

}
