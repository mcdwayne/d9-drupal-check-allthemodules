<?php

namespace Drupal\Tests\xero\Kernel\Normalizer;

use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\xero\Normalizer\XeroListNormalizer;

/**
 * Tests the xero list normalizer with nested typed data.
 *
 * @group xero
 */
class XeroListNestedNormalizerTest extends KernelTestBase {

  use TypedDataTrait;

  /**
   * List items to normalize.
   *
   * @var \Drupal\xero\Plugin\DataType\XeroItemList
   */
  protected $items;

  /**
   * List normalizer.
   *
   * @var \Drupal\xero\Normalizer\XeroListNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization', 'xero'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->normalizer = new XeroListNormalizer();
  }

  /**
   * Asserts that typed data expands to the structured array that Xero expects.
   *
   * @param array $expected
   *   The expected output.
   * @param array $values
   *   The values to set on the typed data object.
   * @param string $format
   *   The serialization format string.
   * @param string $data_type
   *   The Typed Data data type plugin ID.
   *
   * @dataProvider normalizerDataProvider
   */
  public function testNormalize($expected, $values, $format, $data_type) {
    $typedDataManager = $this->getTypedDataManager();
    $definition = $typedDataManager
      ->createListDataDefinition($data_type);
    $this->items = $typedDataManager->create($definition, $values);
    $data = $this->normalizer->normalize($this->items,$format, ['plugin_id' => $data_type]);

    $this->assertEquals($expected, $data);
  }

  /**
   * Provides data for testNormalize().
   *
   * @return array
   *   An array of test parameters.
   */
  public function normalizerDataProvider() {
    $xmlExpectOne = [
      'Contact' => [
        'Name' => 'ABC Company',
        'FirstName' => 'Sam',
        'LastName' => 'Gonzaga',
        'Addresses' => [
          'Address' => [
            'AddressType' => 'STREET',
            'AddressLine1' => 'Corso Vittorio Emanuele, 95',
            'City' => 'Quarrata',
            'Region' => 'PT',
            'PostalCode' => '51039',
            'Country' => 'Italy',
          ],
        ],
        'Phones' => [
          'Phone' => [
            [
              'PhoneType' => 'DEFAULT',
              'PhoneNumber' => '7652874',
              'PhoneAreaCode' => '0359',
              'PhoneCountryCode' => '39',
            ],
            [
              'PhoneType' => 'MOBILE',
              'PhoneNumber' => '7492515',
              'PhoneAreaCode' => '0388',
              'PhoneCountryCode' => '39',
            ],
          ],
        ],
        'UpdatedDateUTC' => '2009-05-14T01:44:26.747',
      ],
    ];
    $valuesOne = [
      [
        'Name' => 'ABC Company',
        'FirstName' => 'Sam',
        'LastName' => 'Gonzaga',
        'Addresses' => [
          [
            'AddressType' => 'STREET',
            'AddressLine1' => 'Corso Vittorio Emanuele, 95',
            'City' => 'Quarrata',
            'Region' => 'PT',
            'PostalCode' => '51039',
            'Country' => 'Italy',
          ],
        ],
        'Phones' => [
          [
            'PhoneType' => 'DEFAULT',
            'PhoneNumber' => '7652874',
            'PhoneAreaCode' => '0359',
            'PhoneCountryCode' => '39',
          ],
          [
            'PhoneType' => 'MOBILE',
            'PhoneNumber' => '7492515',
            'PhoneAreaCode' => '0388',
            'PhoneCountryCode' => '39',
          ],
        ],
        'UpdatedDateUTC' => '2009-05-14T01:44:26.747',
      ],
    ];
    $xmlExpectMultiple = [
      'Contacts' => [
        'Contact' => [
          [
            'Name' => 'ABC Company',
            'FirstName' => 'Sam',
            'LastName' => 'Gonzaga',
          ],
          [
            'Name' => 'DEF Company',
            'FirstName' => 'Alex',
            'LastName' => 'Pirozzi',
          ],
        ],
      ],
    ];
    $jsonExpectOne = [
      'Name' => 'ABC Company',
      'FirstName' => 'Sam',
      'LastName' => 'Gonzaga',
      'Addresses' => [
        [
          'AddressType' => 'STREET',
          'AddressLine1' => 'Corso Vittorio Emanuele, 95',
          'City' => 'Quarrata',
          'Region' => 'PT',
          'PostalCode' => '51039',
          'Country' => 'Italy',
        ],
      ],
      'Phones' => [
        [
          'PhoneType' => 'DEFAULT',
          'PhoneNumber' => '7652874',
          'PhoneAreaCode' => '0359',
          'PhoneCountryCode' => '39',
        ],
        [
          'PhoneType' => 'MOBILE',
          'PhoneNumber' => '7492515',
          'PhoneAreaCode' => '0388',
          'PhoneCountryCode' => '39',
        ],
      ],
      'UpdatedDateUTC' => '2009-05-14T01:44:26.747',
    ];

    $jsonExpectMultiple = [
      'Contacts' => $xmlExpectMultiple['Contacts']['Contact'],
    ];

    $lineItemExpectOne = [
      'LineItem' => [
        'Description' => 'A product',
        'Quantity' => 5.0,
        'UnitAmount' => 1.99,
        'ItemCode' => 'Whatever',
        'AccountCode' => '200',
        'Tracking' => [
          'TrackingCategory' => [
            'Name' => 'Region',
            'Option' => 'West Coast',
          ],
        ],
      ],
    ];
    $lineItemValuesOne = [
      [
        'Description' => 'A product',
        'Quantity' => 5.0,
        'UnitAmount' => 1.99,
        'ItemCode' => 'Whatever',
        'AccountCode' => '200',
        'Tracking' => [
          ['Name' => 'Region', 'Option' => 'West Coast'],
        ],
      ],
    ];

    return [
      [$xmlExpectOne, $valuesOne, 'xml', 'xero_contact'],
      [$xmlExpectMultiple, $xmlExpectMultiple['Contacts']['Contact'], 'xml', 'xero_contact'],
      [$jsonExpectOne, $valuesOne, 'json', 'xero_contact'],
      [$jsonExpectMultiple, $xmlExpectMultiple['Contacts']['Contact'], 'json', 'xero_contact'],
      [$lineItemExpectOne, $lineItemValuesOne, 'xml', 'xero_line_item'],
    ];
  }

}
