<?php

namespace Drupal\Tests\ssf\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ssf\Degenerator;

/**
 * Tests for the Degenerator.
 *
 * @coversDefaultClass \Drupal\ssf\Degenerator
 * @group ssf
 */
class DegeneratorTest extends UnitTestCase {

  /**
   * Provide the testcases for the test.
   *
   * @return array
   *   Array of testcases.
   */
  public function provideTestWords() {
    return [
      [['words' => ['WORDS', 'Words']], ['words']],
      [['words' => ['WORDS', 'Words']], ['words', 'words']],
      [
        [
          'words' => ['WORDS', 'Words'],
          'woRDS' => ['words', 'WORDS', 'Words'],
        ],
        ['words', 'woRDS'],
      ],
      [
        [
          'words' => ['WORDS', 'Words'],
          'sentences' => ['SENTENCES', 'Sentences'],
        ],
        ['words', 'sentences'],
      ],
      [['CAPITALS' => ['capitals', 'Capitals']], ['CAPITALS']],
    ];
  }

  /**
   * Test the retrieval of degenerates of a word.
   *
   * @param array $expected
   *   Expected test result for assertion.
   * @param array $words
   *   Input for test.
   *
   * @dataProvider provideTestWords
   *
   * @covers ::degenerate
   */
  public function testDegenerate(array $expected, array $words) {
    $logger_factory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $degenerator = new Degenerator($logger_factory);

    $this->assertArrayEquals($expected, $degenerator->degenerate($words));
  }

}
