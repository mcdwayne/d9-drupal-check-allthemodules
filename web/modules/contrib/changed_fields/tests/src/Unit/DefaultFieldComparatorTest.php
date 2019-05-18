<?php

namespace Drupal\Tests\changed_fields\Unit;

use Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator
 *
 * @group changed_fields
 */
class DefaultFieldComparatorTest extends UnitTestCase {

  /**
   * @var DefaultFieldComparator
   */
  private $fieldComparator;

  /**
   * @var FieldConfig
   */
  private $fieldDefinitionMock;

  public function setUp() {
    $this->fieldComparator = new DefaultFieldComparator([], '', []);

    $this->fieldDefinitionMock = $this->getMockBuilder('Drupal\field\Entity\FieldConfig')
      ->disableOriginalConstructor()
      ->getMock();

    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');
  }

  /**
   * Check comparison: first value was added.
   */
  public function testFirstValueWasAdded() {
    $this->assertEquals([
      'old_value' => [],
      'new_value' => [
        [
          'value' => 'Text 1',
        ]
      ],
    ], $this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [], [
      [
        'value' => 'Text 1',
      ]
    ]));
  }

  /**
   * Check comparison: last value was deleted.
   */
  public function testLastValueWasDeleted() {
    $this->assertEquals([
      'old_value' => [
        [
          'value' => 'Text 1',
        ]
      ],
      'new_value' => [],
    ], $this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [
      [
        'value' => 'Text 1',
      ]
    ], []));
  }

  /**
   * Check comparison of multi-value fields - add a field value.
   */
  public function testMultipleFieldValuesAddition() {
    $this->assertEquals([
      'old_value' => [
        [
          'value' => 'Text 1',
        ],
      ],
      'new_value' => [
        [
          'value' => 'Text 1',
        ],
        [
          'value' => 'Text 2',
        ],
      ],
    ], $this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [
      [
        'value' => 'Text 1',
      ],
    ], [
      [
        'value' => 'Text 1',
      ],
      [
        'value' => 'Text 2',
      ],
    ]));
  }

  /**
   * Check comparison of multi-value fields - delete a field value.
   */
  public function testMultipleFieldValuesDeletion() {
    $this->assertEquals([
      'old_value' => [
        [
          'value' => 'Text 1',
        ],
        [
          'value' => 'Text 2',
        ],
      ],
      'new_value' => [
        [
          'value' => 'Text 2',
        ],
      ],
    ], $this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [
      [
        'value' => 'Text 1',
      ],
      [
        'value' => 'Text 2',
      ],
    ], [
      [
        'value' => 'Text 2',
      ],
    ]));
  }

  /**
   * Check comparison of multi-value fields - ordering.
   */
  public function testMultipleFieldValuesOrdering() {
    $this->assertEquals([
      'old_value' => [
        [
          'value' => 'Text 1',
        ],
        [
          'value' => 'Text 2',
        ],
      ],
      'new_value' => [
        [
          'value' => 'Text 2',
        ],
        [
          'value' => 'Text 1',
        ],
      ],
    ], $this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [
      [
        'value' => 'Text 1',
      ],
      [
        'value' => 'Text 2',
      ],
    ], [
      [
        'value' => 'Text 2',
      ],
      [
        'value' => 'Text 1',
      ],
    ]));
  }

  /**
   * If values haven't changed - result should be TRUE.
   */
  public function testSameValues() {
    $this->assertTrue($this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [
      [
        'value' => 'Text 1',
      ],
      [
        'value' => 'Text 2',
      ],
    ], [
      [
        'value' => 'Text 1',
      ],
      [
        'value' => 'Text 2',
      ],
    ]));
  }

  /**
   * If values haven't changed - result should be TRUE.
   */
  public function testSameEmptyValues() {
    $this->assertTrue($this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [], []));
  }

  /**
   * Check if getDefaultComparableProperties is never called.
   */
  public function testGetDefaultComparablePropertiesMethodNeverCalled() {
    $fieldComparatorMock = $this->getMockBuilder('\Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator')
      ->setMethods(['getDefaultComparableProperties'])
      ->disableOriginalConstructor()
      ->getMock();

    $fieldComparatorMock->expects($this->never())
      ->method('getDefaultComparableProperties');

    $fieldComparatorMock->compareFieldValues($this->fieldDefinitionMock, [], []);
  }

  /**
   * Check if extendComparableProperties is called.
   */
  public function testExtendComparablePropertiesMethodCalled() {
    $fieldComparatorMock = $this->getMockBuilder('\Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator')
      ->setMethods(['extendComparableProperties'])
      ->disableOriginalConstructor()
      ->getMock();

    $fieldComparatorMock->expects($this->once())
      ->method('extendComparableProperties');

    $fieldComparatorMock->compareFieldValues($this->fieldDefinitionMock, [], []);
  }

}
