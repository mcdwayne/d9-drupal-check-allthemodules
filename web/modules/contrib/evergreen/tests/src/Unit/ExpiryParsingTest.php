<?php

namespace Drupal\Tests\evergreen\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\evergreen\ExpiryParser;

/**
 * Tests the new entity API for the evergreen field type.
 *
 * @group evergreen
 */
class ExpiryParsingTest extends UnitTestCase {

  public function setUp() {
    $this->parser = new ExpiryParser();
  }

  public function testParsingExpiryStrings() {
    $tests = [
      [
        'test' => '30',
        'expected' => 30,
      ],
      [
        'test' => '30 seconds',
        'expected' => 30,
      ],
      [
        'test' => '10 minutes',
        'expected' => 60 * 10,
      ],
      [
        'test' => '10 minute',
        'expected' => 60 * 10,
      ],
      [
        'test' => '10 hours',
        'expected' => (60 * 60) * 10,
      ],
      [
        'test' => '10 hours 10 minutes',
        'expected' => ((60 * 60) * 10) + (60 * 10),
      ],
      [
        'test' => '10 hours, 10 minutes',
        'expected' => ((60 * 60) * 10) + (60 * 10),
      ],
    ];

    $count = 0;
    foreach ($tests as $test) {
      if (!isset($test['debug'])) {
        $test['debug'] = FALSE;
      }
      $this->assertEquals($test['expected'], $this->parser->parse($test['test'], $test['debug']), "Test $count failed: " . $test['test'] . ' != ' . $test['expected']);
      $count++;
    }
  }

}
