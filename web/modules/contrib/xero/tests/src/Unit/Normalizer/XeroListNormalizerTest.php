<?php

namespace Drupal\Tests\xero\Unit\Normalizer;

use Drupal\xero\Plugin\DataType\Account;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\Normalizer\XeroListNormalizer;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\serialization\Normalizer\TypedDataNormalizer;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test cases for Xero Normalization.
 *
 * @covers \Drupal\xero\Normalizer\XeroListNormalizer
 * @group Xero
 */
class XeroListNormalizerTest extends UnitTestCase {

  /**
   * @property array $data
   */
  protected $data;

  /**
   * @property \Drupal\xero\Normalizer\XeroListNormalizer $normalizer
   */
  protected $normalizer;

  /**
   * @property \Drupal\xero\TypedData\Definition\AccountDefinition $accountDefinition
   */
  protected $accountDefinition;

  /**
   * @property \Drupal\Core\TypedData\ListDataDefinitionInterface $listDefinition
   */
  protected $listDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Account data definition setup.
    $this->accountDefinition = AccountDefinition::create('xero_account');
    $this->accountDefinition->setClass('\Drupal\xero\Plugin\DataType\Account');

    // Typed Data Manager setup.
    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->typedDataManager->expects($this->any())
      ->method('createDataDefinition')
      ->with('xero_account')
      ->will($this->returnValue($this->accountDefinition));

    // Mock the container.
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $this->typedDataManager);
    \Drupal::setContainer($container);

    // Now do list data definition creation mocking.
    $this->listDefinition = ListDataDefinition::create('xero_account');
    $this->listDefinition->setClass('\Drupal\xero\Plugin\DataType\XeroItemList');
    $this->listDefinition->setItemDefinition($this->accountDefinition);
    $this->typedDataManager->expects($this->any())
      ->method('createListDataDefinition')
      ->with('xero_account')
      ->will($this->returnValue($this->listDefinition));

    // Create a normalizer.
    $this->typeddata_normalizer = new TypedDataNormalizer();
    $this->complex_normalizer = new ComplexDataNormalizer();
    $this->normalizer = new XeroListNormalizer();
    $this->normalizer->setSerializer(new Serializer([$this->complex_normalizer, $this->normalizer, $this->typeddata_normalizer]));

    // Setup account data to emulate deserialization.
    $this->data = array(
      'Account' => array(
        array(
          'AccountID' => $this->createGuid(),
          'Name' => $this->getRandomGenerator()->word(10),
          'Code' => '200',
          'Type' => 'BANK'
        ),
      ),
    );
  }

  /**
   * Create a Guid.
   *
   * @return string
   *   A valid globally-unique identifier.
   */
  protected function createGuid($braces = TRUE) {
    $hash = strtoupper(hash('ripemd128', md5($this->getRandomGenerator()->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    // A Guid string representation should be output as lower case per UUIDs
    // and GUIDs Network Working Group INTERNET-DRAFT 3.3.
    $guid = strtolower($guid);

    return $guid;
  }

  /**
   * Assert that several Typed Data item can be normalized correctly.
   *
   * @covers \Drupal\xero\Normalizer\XeroNormalizer::normalize
   */
  public function testNormalize() {
    $this->data['Account'][] = array(
      'AccountID' => $this->createGuid(),
      'Name' => $this->getRandomGenerator()->word(10),
      'Code' => '200',
      'Type' => 'BANK'
    );
    $expect = ['Accounts' => $this->data];

    $string_def = DataDefinition::create('string');
    $integer_def = DataDefinition::create('integer');
    $guid[] = new StringData($string_def);
    $guid[] = new StringData($string_def);
    $name[] = new StringData($string_def);
    $name[] = new StringData($string_def);
    $code[] = new IntegerData($integer_def);
    $code[] = new IntegerData($integer_def);
    $type[] = new StringData($string_def);
    $type[] = new StringData($string_def);

    $guid[0]->setValue($this->data['Account'][0]['AccountID']);
    $name[0]->setValue($this->data['Account'][0]['Name']);
    $code[0]->setValue($this->data['Account'][0]['Code']);
    $type[0]->setValue($this->data['Account'][0]['Type']);
    $guid[1]->setValue($this->data['Account'][1]['AccountID']);
    $name[1]->setValue($this->data['Account'][1]['Name']);
    $code[1]->setValue($this->data['Account'][1]['Code']);
    $type[1]->setValue($this->data['Account'][1]['Type']);

    $itemList = XeroItemList::createInstance($this->listDefinition);
    $account = Account::createInstance($this->accountDefinition);
    $account->setValue($this->data['Account'][0], FALSE);

    $account2 = Account::createInstance($this->accountDefinition);
    $account2->setValue($this->data['Account'][1], FALSE);

    $this->typedDataManager->expects($this->any())
      ->method('create')
      ->with($this->listDefinition, $this->data['Account'])
      ->will($this->returnValue($itemList));

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->withConsecutive(
        array($itemList, 0, $account),
        array($itemList, 1, $account2),
        array($account, 'AccountID', $this->data['Account'][0]['AccountID']),
        array($account, 'Code', $this->data['Account'][0]['Code']),
        array($account, 'Name', $this->data['Account'][0]['Name']),
        array($account, 'Type', $this->data['Account'][0]['Type']),
        array($account, 'Description', null),
        array($account, 'TaxType', null),
        array($account, 'EnablePaymentsToAccount', null),
        array($account, 'ShowInExpenseClaims', null),
        array($account, 'Class', null),
        array($account, 'Status', null),
        array($account, 'SystemAccount', null),
        array($account, 'BankAccountNumber', null),
        array($account, 'CurrencyCode', null),
        array($account, 'ReportingCode', null),
        array($account, 'ReportingCodeName', null),
        array($account2, 'AccountID', $this->data['Account'][1]['AccountID']),
        array($account2, 'Code', $this->data['Account'][1]['Code']),
        array($account2, 'Name', $this->data['Account'][1]['Name']),
        array($account2, 'Type', $this->data['Account'][1]['Type'])
      )
      ->will($this->onConsecutiveCalls(
        $account, $account2, $guid[0], $code[0], $name[0], $type[0],
        null, null, null, null, null, null, null, null, null, null, null,
        $guid[1], $code[1], $name[1], $type[1]
      ));

    $itemList->setValue([0 => $account, 1 => $account2]);

    $items = $this->typedDataManager->create($this->listDefinition, $this->data['Account']);

    $data = $this->normalizer->normalize($items, 'xml', array('plugin_id' => 'xero_account'));

    $this->assertEquals($expect, $data, print_r($data, TRUE));
  }

  /**
   * Assert that one Typed Data item can be normalized correctly.
   *
   * @covers \Drupal\xero\Normalizer\XeroNormalizer::normalize
   */
  public function testNormalizeOne() {
    $expect = array(
      'Account' => $this->data['Account'][0],
    );
    $string_def = DataDefinition::create('string');
    $integer_def = DataDefinition::create('integer');
    $guid = new StringData($string_def);
    $name = new StringData($string_def);
    $code = new IntegerData($integer_def);
    $type = new StringData($string_def);

    $guid->setValue($this->data['Account'][0]['AccountID']);
    $name->setValue($this->data['Account'][0]['Name']);
    $code->setValue($this->data['Account'][0]['Code']);
    $type->setValue($this->data['Account'][0]['Type']);

    $itemList = XeroItemList::createInstance($this->listDefinition);
    $account = Account::createInstance($this->accountDefinition);
    $account->setValue($this->data['Account'][0], FALSE);

    $this->typedDataManager->expects($this->any())
      ->method('create')
      ->with($this->listDefinition, $this->data['Account'])
      ->will($this->returnValue($itemList));

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->withConsecutive(
        array($itemList, 0, $account),
        array($account, 'AccountID', $this->data['Account'][0]['AccountID']),
        array($account, 'Code', $this->data['Account'][0]['Code']),
        array($account, 'Name', $this->data['Account'][0]['Name']),
        array($account, 'Type', $this->data['Account'][0]['Type'])
      )
      ->will($this->onConsecutiveCalls($account, $guid, $code, $name, $type));

    $itemList->setValue([0 => $account]);

    $items = $this->typedDataManager->create($this->listDefinition, $this->data['Account']);

    $data = $this->normalizer->normalize($items, 'xml', array('plugin_id' => 'xero_account'));

    $this->assertEquals($expect, $data, print_r($data, TRUE));
  }
}
