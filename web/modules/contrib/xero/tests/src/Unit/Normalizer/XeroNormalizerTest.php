<?php

namespace Drupal\Tests\xero\Unit\Normalizer;

use Drupal\Tests\xero\Unit\XeroDataTestTrait;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\Normalizer\XeroNormalizer;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\serialization\Normalizer\TypedDataNormalizer;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test cases for Xero Normalization.
 *
 * @covers \Drupal\xero\Normalizer\XeroNormalizer
 * @group Xero
 */
class XeroNormalizerTest extends UnitTestCase {

  use XeroDataTestTrait;

  /**
   * @var array $data
   */
  protected $data;

  /**
   * @var \Drupal\xero\Normalizer\XeroNormalizer $normalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Account data definition setup.
    $accountDefinition = AccountDefinition::create('xero_account');
    $listDefinition = ListDataDefinition::createFromDataType('xero_account');
    $listDefinition->setItemDefinition($accountDefinition);

    // Typed Data Manager mockery.
    $this->createTypedDataProphet();
    $typedDataManager  = $this->typedDataManagerProphet->reveal();

    // Mock the container.
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);

    // Setup account data to emulate deserialization.
    $data = array(
      'Accounts' => array(
        'Account' => array(
          array(
            'AccountID' => $this->createGuid(),
            'Name' => $this->getRandomGenerator()->word(10),
            'Code' => '200',
            'Type' => 'BANK',
            'UpdatedDateUTC' => '2009-05-14T01:44:26.747',
          ),
        ),
      ),
    );

    $this->typedDataManagerProphet
      ->createListDataDefinition('xero_account')
      ->willReturn($listDefinition);

    $this->mockTypedData('list', $data['Accounts']['Account'], NULL, $accountDefinition);

    $typedDataManager = $this->typedDataManagerProphet->reveal();
    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $typedDataManager);
    \Drupal::setContainer($container);

    $this->data = $data;

    // Create a normalizer.
    $this->typeddata_normalizer = new TypedDataNormalizer();
    $this->complex_normalizer = new ComplexDataNormalizer();
    $this->normalizer = new XeroNormalizer($typedDataManager);
    $this->normalizer->setSerializer(new Serializer([$this->complex_normalizer, $this->normalizer, $this->typeddata_normalizer]));
  }

  /**
   * Create a GUID.
   *
   * @param boolean $braces
   *   Whether to wrap the GUID in braces.
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
   * Assert that exception is thrown for missing plugin id argument.
   *
   * @expectedException \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function testMissingPluginId() {
    $this->normalizer->denormalize(NULL, '\Drupal\xero\Plugin\DataType\Account', NULL, array());
  }

  /**
   * Assert that returns a list of accounts.
   *
   * @covers \Drupal\xero\Normalizer\XeroNormalizer::denormalize
   */
  public function testDenormalize() {
    /** @var \Drupal\xero\Plugin\DataType\XeroItemList $items */
    $items = $this->normalizer->denormalize($this->data, '\Drupal\xero\Plugin\DataType\Account', NULL, ['plugin_id' => 'xero_account']);

    $this->assertTrue(is_a($items, '\Drupal\xero\Plugin\DataType\XeroItemList'));
  }

  /**
   * Assert that returns a list of 1 account.
   */
  public function testDenormalizeOne() {
    $data = array(
      'Accounts' => array(
        'Account' => $this->data['Accounts']['Account'][0],
      ),
    );

    $items = $this->normalizer->denormalize($data, '\Drupal\xero\Plugin\DataType\Account', NULL, array('plugin_id' => 'xero_account'));
    $this->assertTrue(is_a($items, '\Drupal\xero\Plugin\DataType\XeroItemList'));
  }
}
