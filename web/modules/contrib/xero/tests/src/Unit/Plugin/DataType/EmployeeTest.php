<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\xero\TypedData\Definition\EmployeeDefinition;
use Drupal\xero\Plugin\DataType\Employee;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Assert setting and getting Employee properties.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\Employee
 * @group Xero
 */
class EmployeeTest extends TestBase {

  const XERO_TYPE = 'xero_employee';
  const XERO_TYPE_CLASS = '\Drupal\xero\Plugin\DataType\Employee';
  const XERO_DEFINITION_CLASS = '\Drupal\xero\TypedData\Definition\EmployeeDefinition';

  protected $employee;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create data type.
    $type_class = self::XERO_TYPE_CLASS;
    $this->employee = new $type_class($this->dataDefinition, self::XERO_TYPE);
  }

  /**
   * Test getPropertyDefinitions.
   */
  public function testPropertyDefinitions() {
    $properties = $this->employee->getProperties();

    $this->assertArrayHasKey('EmployeeID', $properties);
    $this->assertArrayHasKey('FirstName', $properties);
    $this->assertArrayHasKey('LastName', $properties);
    $this->assertArrayHasKey('ExternalLink', $properties);
    $this->assertArrayHasKey('Status', $properties);
  }

  /**
   * Test isActive method.
   */
  public function testIsActive() {
    $string_def = DataDefinition::create('string');
    $string = new StringData($string_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->employee, 'Status')
      ->willReturn($string);

    $this->employee->set('Status', 'INACTIVE');
    $this->assertFalse($this->employee->isActive());

    $this->employee->set('Status', 'ACTIVE');
    $this->assertTrue($this->employee->isActive());
  }
}
