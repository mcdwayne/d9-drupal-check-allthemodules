<?php

namespace Drupal\Tests\xero\Kernel\Normalizer;

use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\xero\Normalizer\XeroNormalizer;

/**
 * Tests denormalizer with services enabled.
 *
 * @group xero
 */
class XeroNestedNormalizerTest extends KernelTestBase {

  use TypedDataTrait;

  /**
   * Typed data denormalized from incoming data.
   *
   * @var \Drupal\xero\Plugin\DataType\XeroItemList
   */
  protected $denormalizedData;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization', 'xero'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $data = [
      'TrackingCategories' => [
        'TrackingCategory' => [
          [
            'Name' => 'Region',
            'Status' => 'ACTIVE',
            'TrackingCategoryID' => '351953c4-8127-4009-88c3-f9cd8c9cbe9f',
            'Options' => [
              'Option' => [
                ['Name' => 'West Coast', 'Status' => 'ACTIVE'],
                ['Name' => 'Eastside', 'Status' => 'ACTIVE'],
              ],
            ],
          ],
        ],
      ],
    ];
    $normalizer = new XeroNormalizer($this->getTypedDataManager());
    $this->denormalizedData = $normalizer
      ->denormalize(
        $data,
        '\Drupal\xero\Plugin\DataType\TrackingCategory',
        'xml',
        ['plugin_id' => 'xero_tracking']
      );
  }

  /**
   * Asserts that denormalization works for deeply-nested objects.
   */
  public function testDenormalize() {
    $values = $this->denormalizedData->getValue();
    $this->assertArrayHasKey('Options', $values[0]);
    $this->assertArrayNotHasKey('Option', $values[0]['Options']);
    $this->assertCount(2, $this->denormalizedData->get(0)->get('Options'));
  }

}
