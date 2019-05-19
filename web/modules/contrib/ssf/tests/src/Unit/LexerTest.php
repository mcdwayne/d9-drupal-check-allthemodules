<?php

namespace Drupal\Tests\ssf\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ssf\Lexer;

/**
 * Tests for the Lexer.
 *
 * @coversDefaultClass \Drupal\ssf\Lexer
 * @group ssf
 */
class LexerTest extends UnitTestCase {

  /**
   * Test the isValid function.
   */
  public function testIsValid() {
    $logger_factory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $lexer = new Lexer($logger_factory);

    $is_valid = new \ReflectionMethod($lexer, 'isValid');
    $is_valid->setAccessible(TRUE);

    $this->assertFalse($is_valid->invokeArgs($lexer, ['bayes*']));
    $this->assertFalse($is_valid->invokeArgs($lexer, ['a']));
    $this->assertFalse($is_valid->invokeArgs($lexer, ['abcdefghijklmnopqrstuvwxyzABCDEFG']));
    $this->assertTrue($is_valid->invokeArgs($lexer, ['abcdefgh']));
    $this->assertFalse($is_valid->invokeArgs($lexer, ['12345']));
  }

  /**
   * Tests for the Lexer.
   *
   * @return array
   *   Testcases.
   */
  public function provideTestTokens() {
    return [
      [
        ['test' => 1],
        'test',
      ],
      [
        ['test' => 2],
        'test test',
      ],
      [
        [
          'test' => 1,
          '<p>' => 1,
          '</p>' => 1,
        ],
        '<p>test</p>',
      ],
      [
        [
          'Visit' => 1,
          'Google' => 1,
          'find' => 1,
          'your' => 1,
          'answer' => 1,
          '</a>' => 1,
          '<a...>' => 1,
          'href' => 1,
          'http' => 1,
          'www' => 1,
          'google' => 1,
          'com' => 1,
          'www.google.com' => 1,
        ],
        'Visit <a href="http://www.google.com/">Google</a> to find your answer.',
      ],
      [
        ['bayes*no_tokens' => 1],
        'no',
      ],
    ];
  }

  /**
   * Test the generation of tokens from a text.
   *
   * @param array $expected
   *   Expected test result for assertion.
   * @param string $text
   *   Input for the lexer function.
   *
   * @dataProvider provideTestTokens
   *
   * @covers ::getTokens
   */
  public function testGetTokens(array $expected, $text) {
    $logger_factory = $this->createMock('\Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $lexer = new Lexer($logger_factory);

    $this->assertArrayEquals($expected, $lexer->getTokens($text));
  }

}
