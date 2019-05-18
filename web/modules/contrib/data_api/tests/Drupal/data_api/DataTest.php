<?php

namespace Drupal\data_api;

class DataTest extends \PHPUnit_Framework_TestCase {

  public function testGetDateWithTransform() {
    global $entity;
    $field = array(
      'und' => array(
        array(
          'value' => '2017-04-21 00:00:15',
          'value' => '2017-04-21 00:00:15',
          'timezone' => 'America/Los_Angeles',
          'timezone_db' => 'UTC',
          'date_type' => 'datetime',
        ),
      ),
    );
    $node = (object) array(
      "field_date" => $field,
      'type' => 'event',
    );
    $entity = $node;
    $this->data->setEntityType('node');

    // Create a date one day ahead to make sure our transform works.
    // Keep timezone same so we aren't confused.
    $control = date_create('2017-04-22 00:00:15', timezone_open('UTC'));
    $result = $this->data->getDate($node, 'field_date.0.value', NULL, function ($value) {
      return $value->add(new \DateInterval('P1D'));
    });
    $this->assertEquals($control, $result);
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessageRegExp /final component/
   */
  public function testFinalElementIsNotValueNorValue2() {
    global $entity;
    $node = (object) array(
      'type' => 'event',
      'field_date' => array('und' => array()),
    );
    $entity = $node;
    $this->data->setEntityType('node');

    $subject = new \DateTime();
    $this->data->setDate($node, 'field_date.0.pizza', $subject);
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessageRegExp /format\(\)/
   */
  public function testValueHasNotFormatMethod() {
    global $entity;
    $node = (object) array(
      'type' => 'event',
      'field_date' => array('und' => array()),
    );
    $entity = $node;
    $this->data->setEntityType('node');

    $subject = new BadDateClass();
    $this->data->setDate($node, 'field_date.0.value', $subject);
  }

  /**
   * @expectedException InvalidArgumentException
   * @expectedExceptionMessageRegExp /getTimeZone\(\)/
   */
  public function testValueHasNotGetTimeZoneMethod() {
    global $entity;
    $node = (object) array(
      'type' => 'event',
      'field_date' => array('und' => array()),
    );
    $entity = $node;
    $this->data->setEntityType('node');

    $subject = new \stdClass;
    $this->data->setDate($node, 'field_date.0.value', $subject);
  }

  public function testSetDateRemovesCachedDates() {
    global $entity;
    $tz = timezone_open('America/Los_Angeles');
    $field = array(
      'und' => array(
        array(
          'value' => '2017-04-21 07:00:15',
          'value2' => '2017-05-21 07:00:15',
          'timezone' => $tz->getName(),
          'timezone_db' => 'UTC',
          'date_type' => 'datetime',
          'db' => array(
            'value' => date_create('2017-04-21 00:00:15', $tz),
            'value2' => date_create('2017-05-21 00:00:15', $tz),
          ),
        ),
      ),
    );
    $node = (object) array(
      'type' => 'event',
      'field_date' => $field,
    );
    $entity = $node;
    $this->data->setEntityType('node');


    // Create a localized datetime object to set with
    $subject = date_create('2017-03-21 00:00:15', $tz);
    $this->assertEquals($this->data, $this->data->setDate($node, 'field_date.0.value', $subject));

    // Set value and make sure that we have removed the db cache
    $control = $field;
    $control['und'][0]['value'] = '2017-03-21 07:00:15';
    unset($control['und'][0]['db']['value']);
    $this->assertSame($control, $node->field_date);

    // Set value2 and make sure that we have removed the db cache
    $subject = date_create('2017-06-21 00:00:15', $tz);
    $this->assertEquals($this->data, $this->data->setDate($node, 'field_date.0.value2', $subject));
    $control['und'][0]['value2'] = '2017-06-21 07:00:15';
    unset($control['und'][0]['db']['value2']);
    $this->assertSame($control, $node->field_date);
  }

  public function testSetWhenEntityTypeIsKnownFieldLevelIsEmpty() {
    global $entity;
    $node = new \stdClass;
    $node->type = 'person';
    $control = clone $node;
    $entity = $control;
    $control->field_body = array('und' => array());
    $this->data->setEntityType('node');
    $this->data->set($node, 'field_body', NULL, array());
    $this->assertEquals($control, $node);
    $this->data->set($node, 'field_body', '', array());
    $this->assertEquals($control, $node);
    $this->data->set($node, 'field_body', 0, array());
    $this->assertEquals($control, $node);
    $this->data->set($node, 'field_body', FALSE, array());
    $this->assertEquals($control, $node);
  }

  public function testSetDate() {
    global $entity;
    $control = array(
      'und' => array(
        array(
          'value' => '2017-04-21 07:00:15',
          'value2' => '2017-04-21 07:00:15',
          'timezone' => 'America/Los_Angeles',
          'timezone_db' => 'UTC',
          'date_type' => 'datetime',
        ),
      ),
    );
    $node = (object) array(
      'type' => 'event',
      'field_date' => array(),
    );
    $entity = $node;
    $this->data->setEntityType('node');

    // Create a localized datetime object.
    $subject = date_create('2017-04-21 00:00:15', timezone_open($control['und'][0]['timezone']));
    $this->assertEquals($this->data, $this->data->setDate($node, 'field_date.0.value', $subject));

    $this->assertSame($control, $node->field_date);

    // Now set a null 'value'
    $node->field_date['und'][0]['db']['value'] = date_create('2017-04-21 07:00:15', timezone_open($control['und'][0]['timezone_db']));
    $this->assertEquals($this->data, $this->data->setDate($node, 'field_date.0.value', NULL));
    $this->assertSame(array('und' => array()), $node->field_date);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testGetDateBadKeyThrows() {
    $node = (object) array(
      'type' => 'event',
    );
    $this->data->getDate($node, 'field_date.0.date');
  }

  public function testGetDate() {
    global $entity;
    $subject = array(
      'und' => array(
        array(
          'value' => '2017-04-21 00:00:15',
          'value2' => '2017-08-21 00:00:15',
          'timezone' => 'America/Los_Angeles',
          'timezone_db' => 'UTC',
          'date_type' => 'datetime',
        ),
      ),
    );
    $node = (object) array(
      "field_date" => $subject,
      'type' => 'event',
    );
    $entity = $node;
    $this->data->setEntityType('node');

    $control = date_create($subject['und'][0]['value'], timezone_open($subject['und'][0]['timezone_db']));
    $this->assertEquals($control, $this->data->getDate($node, 'field_date.0.value'));

    $control = date_create($subject['und'][0]['value2'], timezone_open($subject['und'][0]['timezone_db']));
    $this->assertEquals($control, $this->data->getDate($node, 'field_date.0.value2'));
  }

  public function testDoNotGetLanguageWhenFieldButSubjectIsArray() {
    global $entity;
    $edit = array(
      'type' => 'person',
      'field_state' => 'tired',
    );
    $entity = (object) $edit;
    $value = $this->data->setEntityType('node')
                        ->get($edit, 'field_state');
    $this->assertEquals('tired', $value);
  }

  public function testDoNotSetWhenFieldButSubjectIsArray() {
    global $entity;
    $edit = array('type' => 'person');
    $entity = (object) $edit;
    $control = $edit;
    $control['field_state'][0]['value'] = 'sleep';
    $this->data->setEntityType('node')
               ->set($edit, 'field_state.0.value', 'sleep', array());
    $this->assertEquals($control, $edit);
  }

  public function testSetWhenEntityTypeIsKnownValueLevelValueIsZero() {
    global $entity;
    $node = new \stdClass;
    $node->type = 'person';
    $control = clone $node;
    $entity = $control;
    $control->field_state['und'][0]['value'] = 0;
    $this->data->setEntityType('node')
               ->set($node, 'field_state.0.value', 0, array());
    $this->assertEquals($control, $node);
  }

  public function testSetChained() {
    $vars = array('do' => 're');
    $this->data->onlyIf($vars, 'do')
               ->call(function ($value) {
                 return strtoupper($value);
               })
               ->set($vars);
    $this->assertSame('RE', $vars['do']);
  }

  public function testSetEntireArrayWhenFieldIsEmpty() {
    global $entity;
    $entity = new \stdClass;
    $entity->type = 'person';
    $control = clone $entity;
    $entity->field_body = array();

    $items = array(
      array('do' => 're'),
      array('mi' => 'fa'),
    );
    $control->field_body['und'] = $items;

    $this->data->setEntityType('node');
    $this->data->set($entity, 'field_body', $items);
    $this->assertEquals($control, $entity);
  }

  public function testSetNoBundleActsLikeRegular() {
    global $entity;
    $entity = new \stdClass;
    $control = clone $entity;
    $entity->field_body = array();

    // Notice since we don't have a bundle type, this falls back to normal array.
    $control->field_body[0]['value'] = 'lorem';
    $this->data->setEntityType('node')
               ->set($entity, 'field_body.0.value', 'lorem', array());
    $this->assertEquals($control, $entity);
  }

  public function testSetWhenEntityTypeIsKnownFieldLevelArray() {
    global $entity;
    $node = new \stdClass;
    $node->type = 'person';
    $control = clone $node;
    $entity = $control;
    $control->field_body = array('und' => array());
    $this->data->setEntityType('node')
               ->set($node, 'field_body', array(), array());
    $this->assertEquals($control, $node);
  }

  public function testSetWhenEntityTypeIsKnownDeltaLevel() {
    global $entity;
    $node = new \stdClass;
    $node->type = 'person';
    $control = clone $node;
    $entity = $control;
    $value = array('value' => 'lorem');
    $control->field_body['und'][0] = $value;
    $this->data->setEntityType('node')
               ->set($node, 'field_body.0', $value, array());
    $this->assertEquals($control, $node);
  }

  public function testSetWhenEntityTypeIsKnown() {
    global $entity;
    $node = new \stdClass;
    $node->type = 'person';
    $control = clone $node;
    $entity = $control;
    $control->field_body['und'][0]['value'] = 'lorem';
    $this->data->setEntityType('node')
               ->set($node, 'field_body.0.value', 'lorem', array());
    $this->assertEquals($control, $node);
  }

  public function testSetWhenNoEntityType() {
    $node = new \stdClass;
    $control = clone $node;
    $control->field_body['und'][0]['value'] = 'lorem';
    $this->data->set($node, 'field_body.und.0.value', 'lorem', array());
    $this->assertEquals($control, $node);
  }

  public function testCallbackFiredWhenSubjectIsEmpty() {
    $called = FALSE;
    $this->data->get(NULL, 'do.re.mi', 'default', function ($value) use (&$called) {
      $called = TRUE;
    });
    $this->assertTrue($called);
  }

  public function testWhenTheFieldHasNoItems() {
    global $entity;
    $entity = (object) array(
      "field_name" => NULL,
      'type' => 'person',
    );
    $this->data->setEntityType('node')
               ->get($entity, 'field_name.0.value', 'apple');
    $this->assertSame('apple', $this->data->get($entity, 'field_name.0.value', 'apple'));
  }

  public function testItemLevel() {
    global $entity;
    $subject = array('und' => array(array('value' => 'Scott Johnson')));
    $node = (object) array(
      "field_name" => $subject,
      'type' => 'person',
    );
    $entity = $node;
    $control = $subject['und'][0]['value'];
    $this->data->setEntityType('node');
    $this->assertSame($control, $this->data->get($node, 'field_name.0.value'));

    // This will test the callback and the default.
    $doesExist = NULL;
    $this->assertSame('JACK', $this->data->get($node, 'field_name.0.value', 'Jack', function ($value, $default, $exists) use (&$doesExist) {
      $doesExist = $exists;

      return strtoupper($default);
    }));
    $this->assertTrue($doesExist);

    // This will test the callback and the default.
    $doesExist = NULL;
    $this->assertSame('JACK', $this->data->get($node, 'field_name.0.indicator', 'Jack', function ($value, $default, $exists) use (&$doesExist) {
      $doesExist = $exists;

      return strtoupper($default);
    }));
    $this->assertFalse($doesExist);
  }

  public function testItemLevelWithFourPathComponents() {
    global $entity;
    $node = (object) array(
      'type' => 'person',
    );

    // This is not feasible in drupal 7, but let's test it anyway.
    $node->field_name['und'][0]['value']['first'] = 'Aaron';

    $entity = $node;
    $this->data->setEntityType('node');
    $this->assertSame('Aaron', $this->data->get($node, 'field_name.0.value.first'));
  }

  public function testDeltaLevel() {
    global $entity;
    $subject = array('und' => array(array('value' => 'Scott Johnson')));
    $node = (object) array(
      "field_name" => $subject,
      'type' => 'person',
    );
    $entity = $node;
    $control = $subject['und'][0];
    $this->data->setEntityType('node');
    $this->assertSame($control, $this->data->get($node, 'field_name.0'));
  }

  public function testDeltaLevelCallback() {
    global $entity;
    $subject = array('und' => array(array('value' => 'Scott Johnson')));
    $node = (object) array(
      "field_name" => $subject,
      'type' => 'person',
    );
    $entity = $node;
    $control = $subject['und'][0];
    $this->data->setEntityType('node');

    // Test the callback
    $this->assertSame(json_encode($control), $this->data->get($node, 'field_name.0', NULL, function ($value) {
      return json_encode($value);
    }));
  }

  public function testFieldLevel() {
    global $entity;
    $node = (object) array(
      'type' => 'person',
    );
    $node->field_name['und'][0]['value'] = 'Scott Johnson';
    $entity = $node;
    $control = $node->field_name['und'];
    $this->data->setEntityType('node');
    $this->assertSame($control, $this->data->get($node, 'field_name'));

    // Test the callback
    $this->assertSame(json_encode($control), $this->data->get($node, 'field_name', NULL, function ($value) {
      return json_encode($value);
    }));
  }

  public function testEmptySubject() {
    $this->assertSame('froggy', $this->data->get(array(), 'field', 'froggy'));
  }

  public function testNid() {
    global $entity;
    $subject = (object) array('nid' => 27);
    $entity = $subject;
    $this->assertSame(27, $this->data->setEntityType('node')
                                     ->get($subject, 'nid'));
  }

  public function testNidNoEntityType() {
    global $entity;
    $subject = (object) array('nid' => 27);
    $entity = $subject;
    $this->assertSame(27, $this->data->get($subject, 'nid'));
  }

  public function setUp() {
    $this->data = new DataMock;
  }
}
