<?php

namespace Drupal\Tests\radioactivity\Unit;

use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\radioactivity\Incident;

/**
 * @coversDefaultClass \Drupal\radioactivity\Incident
 * @group radioactivity
 */
class IncidentTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Initiate the Settings singleton used by this test.
    new Settings([
      'hash_salt' => 'liesjeleerdelotjelopen',
    ]);
  }

  /**
   * @covers ::getFieldName
   * @covers ::getEntityTypeId
   * @covers ::getEntityId
   * @covers ::getEnergy
   */
  public function testGetters() {
    $incident = new Incident('field_name', 'entity_type', '99', 5.5, '1234567890');

    $this->assertEquals($incident->getFieldName(), 'field_name');
    $this->assertEquals($incident->getEntityTypeId(), 'entity_type');
    $this->assertEquals($incident->getEntityId(), '99');
    $this->assertEquals($incident->getEnergy(), 5.5);
  }

  /**
   * @covers ::createFromPostData
   */
  public function testCreateFromPostData() {
    $incident = Incident::createFromPostData([
      'fn' => 'field_name',
      'et' => 'entity_type',
      'id' => '99',
      'e' => 5.5,
      'h' => '1234567890',
    ]);

    $this->assertEquals($incident->getFieldName(), 'field_name');
    $this->assertEquals($incident->getEntityTypeId(), 'entity_type');
    $this->assertEquals($incident->getEntityId(), '99');
    $this->assertEquals($incident->getEnergy(), 5.5);
  }

  /**
   * @covers ::toJson
   */
  public function testJson() {
    $incident = new Incident('field_name', 'entity_type', '99', 5.5, '1234567890');
    $this->assertEquals($incident->toJson(), '{"fn":"field_name","et":"entity_type","id":"99","e":5.5,"h":"5aa2ff01ac75da55751051a55021092768d079c5"}');
  }

  /**
   * @covers ::isValid
   */
  public function testValidHash() {
    $incident = new Incident('field_name', 'entity_type', '99', 5.5, '1234567890');
    $this->assertFalse($incident->isValid());

    $incident = new Incident('field_name', 'entity_type', '99', 5.5, '5aa2ff01ac75da55751051a55021092768d079c5');
    $this->assertTrue($incident->isValid());
  }

}
