<?php

namespace Drupal\Tests\commerce_xero\Unit\Form;

use Drupal\commerce_xero\Form\StrategyForm;
use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the commerce xero stratey form.
 *
 * @group commerce_xero
 */
class StrategyFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Mock Xero API module functionality.
    $query = $this->getXeroQueryMock();
    $queryFactoryProphet = $this->prophesize('\Drupal\xero\XeroQueryFactory');
    $queryFactoryProphet->get()->willReturn($query);

    // Mock Commerce Payment module functionality.
    $storageProphet = $this->prophesize('\Drupal\Core\Entity\EntityStorageInterface');
    $storageProphet->loadMultiple()->willReturn([]);

    $entityTypeProphet = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entityTypeProphet->getStorage('commerce_payment_gateway')->willReturn($storageProphet->reveal());

    $dataTypeProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroDataTypeManager');
    $dataTypeProphet
      ->getDefinitions()
      ->willReturn([
        'commerce_xero_bank_transaction' => [
          'id' => 'commerce_xero_bank_transaction',
          'label' => 'Bank Transaction',
          'data_type' => 'xero_bank_transaction',
          'settings' => [],
        ],
      ]);

    $processorProphet = $this->prophesize('\Drupal\commerce_xero\CommerceXeroProcessorManager');

    $cache = new NullBackend('xero_query');

    // Set the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('entity_type.manager', $entityTypeProphet->reveal());
    $container->set('xero.query.factory', $queryFactoryProphet->reveal());
    $container->set('cache.xero_query', $cache);
    $container->set('commerce_xero_data_type.manager', $dataTypeProphet->reveal());
    $container->set('commerce_xero_processor.manager', $processorProphet->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * Asserts that the container injection method works.
   */
  public function testCreate() {
    $this->assertInstanceOf('\Drupal\Core\Entity\EntityForm', StrategyForm::create(\Drupal::getContainer()));
  }

  /**
   * Asserts that the form is built correctly.
   *
   * @param string $key
   *   The form key to assert.
   *
   * @dataProvider buildFormProvider
   */
  public function testBuildForm($key) {
    // Mock the module handler service.
    $moduleProphet = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleProphet->getImplementations(Argument::any())->willReturn([]);

    $strategyProphet = $this->prophesize('\Drupal\commerce_xero\Entity\CommerceXeroStrategy');
    $strategyProphet->getEntityTypeId()->willReturn('commerce_xero_strategy');
    $strategyProphet->get('payment_gateway')->willReturn(NULL);
    $strategyProphet->get('status')->willReturn(NULL);
    $strategyProphet->get('xero_type')->willReturn('commerce_xero_bank_transaction');
    $strategyProphet->get('bank_account')->willReturn(NULL);
    $strategyProphet->get('revenue_account')->willReturn(NULL);
    $strategyProphet->label()->willReturn(NULL);
    $strategyProphet->id()->willReturn(NULL);
    $strategyProphet->isNew()->willReturn(TRUE);

    $form = [];
    $form_state = new FormState();
    $instance = StrategyForm::create(\Drupal::getContainer());

    $instance->setEntity($strategyProphet->reveal());
    $instance->setModuleHandler($moduleProphet->reveal());

    $this->assertArrayHasKey($key, $instance->buildForm($form, $form_state));
  }

  /**
   * Get the xero query mock.
   *
   * @param \Drupal\xero\TypedData\XeroTypeInterface[] $data
   *   An array of xero types.
   *
   * @return mixed
   *   The Xero query object.
   */
  protected function getXeroQueryMock(array $data = []) {
    $query = $this->getMockBuilder('\Drupal\xero\XeroQuery')
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->any())
      ->method('setFormat')
      ->willReturnSelf();
    $query->expects($this->any())
      ->method('setType')
      ->willReturnSelf();
    $query->expects($this->any())
      ->method('setMethod')
      ->willReturnSelf();
    $query->expects($this->any())
      ->method('addCondition')
      ->willReturnSelf();
    $query->expects($this->any())
      ->method('execute')
      ->willReturn($data);

    return $query;
  }

  /**
   * Provides test data for ::testBuilForm().
   *
   * @returns array
   *   An array of form keys to assert.
   */
  public function buildFormProvider() {
    return [
      ['name'],
      ['id'],
      ['bank_account'],
      ['revenue_account'],
      ['xero_type'],
      ['payment_gateway'],
      ['status'],
    ];
  }

}
