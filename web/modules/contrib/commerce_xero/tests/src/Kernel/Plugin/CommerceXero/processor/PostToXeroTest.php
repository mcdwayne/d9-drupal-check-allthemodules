<?php

namespace Drupal\Tests\commerce_xero\Kernel\Plugin\CommerceXero\processor;

use Drupal\commerce_xero\Plugin\CommerceXero\processor\PostToXero;
use Drupal\KernelTests\KernelTestBase;
use Drupal\xero\Plugin\DataType\BankTransaction;
use Drupal\xero\TypedData\Definition\BankTransactionDefinition;
use Prophecy\Argument;

/**
 * Tests the post to xero plugin.
 *
 * @group commerce_xero
 */
class PostToXeroTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce',
    'commerce_order',
    'commerce_store',
    'commerce_price',
    'commerce_payment',
    'commerce_payment_example',
    'commerce_xero',
    'xero',
    'serialization',
  ];

  /**
   * Asserts that the process method is called.
   *
   * @param bool $expected
   *   The expected return value.
   *
   * @dataProvider processProvider
   *
   * @covers \Drupal\commerce_xero\Plugin\CommerceXero\processor\PostToXero::process
   */
  public function testProcess($expected) {
    // Object chaining is not supported by prophecy because of opinions.
    $query = $this->getMockBuilder('\Drupal\xero\XeroQuery')
      ->disableOriginalConstructor()
      ->getMock();

    $query->expects($this->once())
      ->method('setType')
      ->with('xero_bank_transaction')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('setFormat')
      ->with('xml')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('setMethod')
      ->with('post')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('setData')
      ->willReturnSelf();

    $result = FALSE;
    if ($expected) {
      $typedDataManager = $this->container->get('typed_data_manager');
      $listDefinition = $typedDataManager->createListDataDefinition('xero_bank_transaction');
      $result = $typedDataManager->create($listDefinition);
      $result->appendItem([
        'BankTransactionID' => 'd20b6c54-7f5d-4ce6-ab83-55f609719126',
      ]);
    }

    $query->expects($this->once())
      ->method('execute')
      ->willReturn($result);

    $this->container->set('xero.query', $query);

    $configuration = [
      'id' => 'commerce_xero_send',
      'settings' => [],
    ];
    $definition = [
      'id' => 'commere_xero_send',
      'label' => 'Post to Xero',
      'types' => [],
      'settings' => [],
      'execution' => 'send',
      'required' => TRUE,
      'class' => '\Drupal\commerce_xero\Plugin\CommerceXero\processor\PostToXero',
    ];
    $plugin = PostToXero::create(
      $this->container,
      $configuration,
      'commerce_xero_send',
      $definition
    );

    // Create data type to "post".
    $dataDefinition = BankTransactionDefinition::create('xero_bank_transaction');
    $data = BankTransaction::createInstance($dataDefinition);

    // @todo mock field definition.
    $fieldDefinitionProphet = $this->prophesize('\Drupal\Core\FieldDefinitionInterface');
    $fieldItemListProphet = $this->prophesize('\Drupal\Core\Field\FieldItemListInterface');
    $fieldItemListProphet->count()->willReturn(0);
    $fieldItemListProphet->appendItem(Argument::any());

    $paymentProphet = $this->prophesize('\Drupal\commerce_payment\Entity\PaymentInterface');
    $paymentProphet
      ->getFieldDefinition('xero_transaction')
      ->willReturn($fieldDefinitionProphet->reveal());
    $paymentProphet
      ->get('xero_transaction')
      ->willReturn($fieldItemListProphet->reveal());
    $paymentProphet
      ->set('xero_transaction', Argument::any(), TRUE)
      ->willReturn(NULL);
    $paymentProphet->save()->willReturn(TRUE);

    $this->assertEquals($expected, $plugin->process($paymentProphet->reveal(), $data));
  }

  /**
   * Data provider for the process test.
   *
   * @return array
   *   An array of test arguments.
   */
  public function processProvider() {
    return [
      'xero failure' => [FALSE],
      'xero success' => [TRUE],
    ];
  }

}
