<?php

namespace Drupal\tacjs;

use Drupal\Tests\UnitTestCase;
use Drupal\tacjs\TacjsSettings;
use Drupal\tacjs\Unit;

/**
 * Simple test to ensure that asserts pass.
 *
 * @group tacjs
 */
class UnitTest extends UnitTestCase {


  protected $unit;
  protected $fixture;

  /**
   * Before a test method is run, setUp() is invoked.
   * Create new unit object.
   */
  public function setUp() {
    $this->unit = new TacjsSettings();
    $this->fixture = new Unit();
  }

  /**
   * @covers Drupal\tacjs\TacjsSettings::getFields
   */
  public function testGetFields() {
    $values =  [
    "purechat" => "purechat"
    ];
    $data = TacjsSettings::SUPPORT;
    $result = [
                0 => [
                  "value" => "purechat",
                  "name" => "Purechat",
                ]
              ];
    $this->assertEquals($result, $this->fixture->getFields($values,$data));
  }

  /**
   * @covers Drupal\tacjs\TacjsSettings::getFields
   */
  public function testSerializeValuesForm() {
    $values = [
      "type_social_networks" => [
        "facebook" => "facebook"
      ],
      "submit" => [],
      "form_build_id" => "",
      "form_token" => "",
      "form_id" => "",
      "op" =>[],
      ];
    $result = [
      "type_social_networks" => [
        "facebook" => "facebook"
      ],
    ];
    $result = serialize($result);
    $this->assertEquals($result, $this->unit->serializeValuesForm($values));
  }

  /**
   * Once test method has finished running, whether it succeeded or failed, tearDown() will be invoked.
   * Unset the $unit object.
   */
  public function tearDown() {
    unset($this->unit);
  }


}