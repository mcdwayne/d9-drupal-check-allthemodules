<?php

namespace Drupal\Tests\dream_fields\Unit;

use Drupal\dream_fields\UniqueFieldMachineNameGenerator;
use Drupal\dream_fields\UniqueMachineNameGenerator;
use Drupal\Tests\UnitTestCase;

/**
 * A unit test to verify the machine name generation works.
 *
 * @group dream_fields
 */
class MachineNameGeneratorTest extends UnitTestCase {

  /**
   * Test the machine name generation.
   *
   * @dataProvider machineNameTestCases
   */
  public function testMachineNameGeneration($input_string, $expected_machine_name, $existing_field_names = []) {
    $field_storage_mock = $this->getMock('\Drupal\Core\Entity\EntityStorageInterface');
    $field_storage_mock
      ->expects($this->any())
      ->method('loadByProperties')
      ->willReturnCallback(function ($properties) use ($existing_field_names) {
        return in_array($properties['lookup'], $existing_field_names);
      });

    $type_manager = $this->getMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $type_manager
      ->expects($this->any())
      ->method('getStorage')
      ->willReturn($field_storage_mock);

    $generator = new UniqueMachineNameGenerator($type_manager, 'foo', 'lookup');
    $this->assertEquals($expected_machine_name, $generator->getMachineName($input_string));
  }

  /**
   * Data provider for the ::testMachineNameGeneration method.
   */
  public function machineNameTestCases() {
    return [
      'Simple machine name' => [
        'Some Field Name',
        'some_field_name',
      ],
      'Field prefix machine name' => [
        'field_Some Field Name',
        'field_some_field_name',
      ],
      'Field Name Collision' => [
        'Foo Field Name',
        'foo_field_name_0',
        ['foo_field_name'],
      ],
      'Multiple field Name collisions' => [
        'Foo Field Name',
        'foo_field_name_3',
        [
          'foo_field_name',
          'foo_field_name_0',
          'foo_field_name_1',
          'foo_field_name_2',
        ],
      ],
      'Trimmed field name' => [
        'This is a very long field name which should be trimmed',
        'this_is_a_very_long_field_name',
      ],
      'Trimmed field name with collision' => [
        'This is a very long field name which should be trimmed',
        'this_is_a_very_long_field_nam_0',
        [
          'this_is_a_very_long_field_name',
        ]
      ],
      'Trimmed field name with collision (multi-digit collision)' => [
        'This is a very long field name which should be trimmed',
        'this_is_a_very_long_field_na_12',
        [
          'this_is_a_very_long_field_name',
          'this_is_a_very_long_field_na_11',
          'this_is_a_very_long_field_nam_0',
          'this_is_a_very_long_field_nam_1',
          'this_is_a_very_long_field_nam_2',
          'this_is_a_very_long_field_nam_3',
          'this_is_a_very_long_field_nam_4',
          'this_is_a_very_long_field_nam_5',
          'this_is_a_very_long_field_nam_6',
          'this_is_a_very_long_field_nam_7',
          'this_is_a_very_long_field_nam_8',
          'this_is_a_very_long_field_nam_9',
          'this_is_a_very_long_field_na_10',
        ],
      ],
    ];
  }

}
