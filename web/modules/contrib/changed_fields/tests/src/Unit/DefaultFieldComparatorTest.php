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

  /**
   * Call protected/private method of a class.
   *
   * @param object &$object    Instantiated object that we will run method on.
   * @param string $methodName Method name to call
   * @param array  $parameters Array of parameters to pass into method.
   *
   * @return mixed Method return.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = array()) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->fieldComparator = new DefaultFieldComparator([], '', []);
    $this->fieldDefinitionMock = $this->getMockBuilder('Drupal\field\Entity\FieldConfig')
      ->disableOriginalConstructor()
      ->setMethods(['getType', 'getSetting'])
      ->getMock();
  }

  /**
   * Default field comparator: string field's property(s).
   */
  public function testStringFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: string_long field's property(s).
   */
  public function testStringLongFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string_long');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: text field's property(s).
   */
  public function testTextFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('text');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: text_long field's property(s).
   */
  public function testTextLongFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('text_long');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: boolean field's property(s).
   */
  public function testBooleanFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('boolean');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: integer field's property(s).
   */
  public function testIntegerFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('integer');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: float field's property(s).
   */
  public function testFloatFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('float');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: decimal field's property(s).
   */
  public function testDecimalFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('decimal');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: datetime field's property(s).
   */
  public function testDateTimeFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('datetime');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: date range field's property(s).
   */
  public function testDateRangeFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('daterange');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value', 'end_value'], $properties);
  }

  /**
   * Default field comparator: email field's property(s).
   */
  public function testEmailFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('email');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: telephone field's property(s).
   */
  public function testTelephoneFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('telephone');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: list_integer field's property(s).
   */
  public function testListIntegerFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('list_integer');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: list_float field's property(s).
   */
  public function testListFloatFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('list_float');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: list_string field's property(s).
   */
  public function testListStringFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('list_string');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value'], $properties);
  }

  /**
   * Default field comparator: text_with_summary field's property(s).
   */
  public function testTextWithSummaryFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('text_with_summary');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['value', 'summary'], $properties);
  }

  /**
   * Default field comparator: entity_reference field's property(s).
   */
  public function testEntityReferenceFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('entity_reference');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['target_id'], $properties);
  }

  /**
   * Default field comparator: link field's property(s).
   */
  public function testLinkFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('link');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['uri', 'title'], $properties);
  }

  /**
   * Default field comparator: file field's property(s) (without description).
   */
  public function testFileFieldWithoutDescriptionProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('file');

    $this->fieldDefinitionMock->expects($this->once())
      ->method('getSetting')
      ->with('description_field')
      ->willReturn(FALSE);

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['target_id'], $properties);
  }

  /**
   * Default field comparator: file field's property(s) (with description).
   */
  public function testFileFieldWithDescriptionProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('file');

    $this->fieldDefinitionMock->expects($this->once())
      ->method('getSetting')
      ->with('description_field')
      ->willReturn(TRUE);

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['target_id', 'description'], $properties);
  }

  /**
   * Default field comparator: image field's property(s) (without alt and title).
   */
  public function testImageWithoutAltAndTitleFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('image');

    $this->fieldDefinitionMock->expects($this->any())
      ->method('getSetting')
      ->willReturn(FALSE);

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['width', 'height', 'target_id'], $properties);
  }

  /**
   * Default field comparator: image field's property(s) (with alt but without
   * title).
   */
  public function testImageWithAltWithoutTitleFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('image');

    $this->fieldDefinitionMock->expects($this->at(1))
      ->method('getSetting')
      ->with('alt_field')
      ->willReturn(TRUE);

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['width', 'height', 'target_id', 'alt'], $properties);
  }

  /**
   * Default field comparator: image field's property(s) (without alt but with
   * title).
   */
  public function testImageWithoutAltWithTitleFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('image');

    $this->fieldDefinitionMock->expects($this->at(2))
      ->method('getSetting')
      ->with('title_field')
      ->willReturn(TRUE);

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['width', 'height', 'target_id', 'title'], $properties);
  }

  /**
   * Default field comparator: image field's property(s) (with alt and title).
   */
  public function testImageWithAltAndTitleFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('image');

    $this->fieldDefinitionMock->expects($this->any())
      ->method('getSetting')
      ->willReturn(TRUE);

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals(['width', 'height', 'target_id', 'alt', 'title'], $properties);
  }

  /**
   * Default field comparator: unknown field's property(s).
   */
  public function testUnknownFieldProperties() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('unknown');

    $properties = $this->invokeMethod($this->fieldComparator, 'getComparableProperties', [$this->fieldDefinitionMock]);
    $this->assertArrayEquals([], $properties);
  }

  /**
   * Check comparison: first value was added.
   */
  public function testFirstValueWasAdded() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $this->assertArrayEquals([
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
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $this->assertArrayEquals([
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
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $this->assertArrayEquals([
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
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $this->assertArrayEquals([
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
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $this->assertArrayEquals([
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
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

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
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $this->assertTrue($this->fieldComparator->compareFieldValues($this->fieldDefinitionMock, [], []));
  }

  /**
   * Check if getDefaultComparableProperties is never called.
   */
  public function testGetDefaultComparablePropertiesMethodNeverCalled() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $field_comparator_mock = $this->getMockBuilder('\Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator')
      ->setMethods(['getDefaultComparableProperties'])
      ->disableOriginalConstructor()
      ->getMock();

    $field_comparator_mock->expects($this->never())
      ->method('getDefaultComparableProperties');

    $field_comparator_mock->compareFieldValues($this->fieldDefinitionMock, [], []);
  }

  /**
   * Check if extendComparableProperties is called.
   */
  public function testExtendComparablePropertiesMethodCalled() {
    $this->fieldDefinitionMock->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $field_comparator_mock = $this->getMockBuilder('\Drupal\changed_fields\Plugin\FieldComparator\DefaultFieldComparator')
      ->setMethods(['extendComparableProperties'])
      ->disableOriginalConstructor()
      ->getMock();

    $field_comparator_mock->expects($this->once())
      ->method('extendComparableProperties');

    $field_comparator_mock->compareFieldValues($this->fieldDefinitionMock, [], []);
  }

}
