<?php

namespace Drupal\Tests\xero\Unit\Plugin\Field\FieldType;

use Drupal\xero\Plugin\Field\FieldType\XeroReference;
use Drupal\Tests\Core\Field\BaseFieldDefinitionTestBase;

/**
 * Test the constraint system for Xero Guid strings.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\Field\FieldType\XeroReference
 * @group Xero
 */
class XeroReferenceTest extends BaseFieldDefinitionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getPluginId() {
    return 'xero_reference';
  }

  /**
   * {@inheritdoc}
   */
  protected function getModuleAndPath() {
    return array('xero', dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    require_once realpath($this->root) . '/core/includes/bootstrap.inc';

    // Set the typed data manager service after mocking (again).
    $this->typedDataManager = $this->getMockBuilder('\Drupal\Core\TypedData\TypedDataManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->typedDataManager->expects($this->any())
      ->method('getDefaultConstraints')
      ->willReturn([]);
    // Validation constraint manager setup.
    $this->validation_constraint_manager = $this->getMockBuilder('\Drupal\Core\Validation\ConstraintManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->validation_constraint_manager->expects($this->any())
      ->method('create')
      ->willReturn([]);
    $this->typedDataManager->expects($this->any())
      ->method('getValidationConstraintManager')
      ->willReturn($this->validation_constraint_manager);

    // Set the container.
    $container = \Drupal::getContainer();
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('validation.constraint', $this->validation_constraint_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Test get columns
   */
  public function testGetColumns() {
    $columns = [
      'guid' => array('type' => 'varchar', 'length' => 36, 'not null' => TRUE),
      'label' => array('type' => 'varchar', 'length' => 255, 'not null' => FALSE),
      'type' => array('type' => 'varchar', 'length' => 100, 'not null' => TRUE),
    ];
    $this->assertSame($columns, $this->definition->getColumns());
  }

  /**
   * Test main property name.
   */
  public function testMainPropertyName() {
    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with('field_item:xero_reference')
      ->willReturn(['class' => '\Drupal\xero\Plugin\Field\FieldType\XeroReference']);
    $this->assertEquals('guid', $this->definition->getMainPropertyName());
  }

  /**
   * Test property definitions.
   */
  public function testPropertyDefinitions() {
    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with('field_item:xero_reference', TRUE)
      ->willReturn(['class' => '\Drupal\xero\Plugin\Field\FieldType\XeroReference']);
    $definitions = $this->definition->getPropertyDefinitions();

    $this->assertEquals('GUID', $definitions['guid']->getLabel());
    $this->assertEquals('Label', $definitions['label']->getLabel());
    $this->assertEquals('Type', $definitions['type']->getLabel());
    $constraint = $definitions['type']->getConstraint('Choice');
    $this->assertTrue(isset($constraint));
  }

  /**
   * Test getters and setters.
   */
  public function testProperties() {
    $guid = $this->createGuid(FALSE);
    $guid_with_braces = '{' . $guid . '}';

    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with('field_item:xero_reference', TRUE)
      ->willReturn(['class' => '\Drupal\xero\Plugin\Field\FieldType\XeroReference']);

    $type = new XeroReference($this->definition, 'xero_reference');

    // @todo this probably makes this test have very little value?
    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($type, 'guid')
      ->willReturn($guid);

    $this->assertTrue($type->isEmpty());

    $type->set('guid', 0);
    $this->assertTrue($type->isEmpty());

    $type->set('guid', '{}');
    $this->assertFalse($type->isEmpty());

    $type->set('guid', $guid_with_braces);
    $type->preSave();
    $this->assertEquals($guid, $type->get('guid'));
  }

  /**
   * Create a Guid with or without curly braces.
   *
   * @param $braces
   *   (Optional) Return Guid wrapped in curly braces.
   * @return string
   *   Guid string.
   */
  protected function createGuid($braces = TRUE) {
    $hash = strtolower(hash('ripemd128', md5($this->getRandomGenerator()->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    if ($braces) {
      return '{' . $guid . '}';
    }

    return $guid;
  }

}
