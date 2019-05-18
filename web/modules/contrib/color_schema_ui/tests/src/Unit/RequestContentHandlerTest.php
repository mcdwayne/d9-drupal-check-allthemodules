<?php

namespace Drupal\Tests\color_schema_ui\Unit;

use Drupal\color_schema_ui\RequestContentHandler;
use Drupal\Tests\UnitTestCase;


class RequestContentHandlerTest extends UnitTestCase {

  /**
   * @dataProvider provideJSONRGBData
   */
  public function testComputeJSONToVariableRGBPair(string $json): void {
    $requestContentHandler = new RequestContentHandler();

    $expectedObject = new \stdClass();
    $expectedObject->header_background_color = 'rgb(38,187,225)';

    self::assertEquals($expectedObject, $requestContentHandler->computeJSONToVariableRGBPair($json));
  }

  public function provideJSONRGBData() {
    return [
      ['{"header_background_color":"rgb(38,187,225)"}']
    ];
  }

}
