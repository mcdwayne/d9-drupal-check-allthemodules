<?php

namespace Drupal\Tests\taxonomy_scheduler\Unit;

use Drupal\taxonomy_scheduler\Exception\TaxonomySchedulerException;
use Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem;
use Drupal\Tests\UnitTestCase;

/**
 * Class TaxonomyFieldStorageItemTest.
 */
class TaxonomyFieldStorageItemTest extends UnitTestCase {

  /**
   * Provides data for testInvalidData.
   */
  public function invalidDataProvider() {
    return [
      [[]],
      [['vocabularies' => []]],
      [
        [
          'vocabularies' => ['test'],
          'fieldRequired' => 'b',
          'fieldName' => 'test',
          'fieldLabel' => 'Test',
        ],
      ],
    ];
  }

  /**
   * Tests if data is not valid (sad path).
   *
   * @param array $data
   *   The data array.
   *
   * @dataProvider invalidDataProvider
   *
   * @return \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem
   *   The TaxonomyFieldStorageItem.
   */
  public function testInvalidData(array $data): TaxonomyFieldStorageItem {
    $this->expectException(TaxonomySchedulerException::class);
    return new TaxonomyFieldStorageItem($data);
  }

  /**
   * Tests if data is valid (happy path).
   *
   * @covers \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem
   */
  public function testValidData(): void {
    $data = [
      'vocabularies' => ['test'],
      'fieldName' => 'test',
      'fieldLabel' => 'Test',
      'fieldRequired' => 1,
    ];
    $taxonomyFieldStorageItem = new TaxonomyFieldStorageItem($data);

    $this->assertInstanceOf(TaxonomyFieldStorageItem::class, $taxonomyFieldStorageItem);
    $this->assertArrayEquals(['test'], $taxonomyFieldStorageItem->getVocabularies());
    $this->assertEquals('test', $taxonomyFieldStorageItem->getFieldName());
    $this->assertEquals('Test', $taxonomyFieldStorageItem->getFieldLabel());
    $this->assertEquals(1, $taxonomyFieldStorageItem->getFieldRequired());
  }

}
