<?php

namespace Drupal\Tests\contentserialize\Unit;

use Drupal\contentserialize\Traversables;
use Drupal\Tests\UnitTestCase;

/**
 * Provides test for the Traversables helper class.
 *
 * @coversDefaultClass \Drupal\contentserialize\Traversables
 *
 * @group contentserialize
 */
class TraversablesTest extends UnitTestCase {

  /**
   * @covers ::chunk
   *
   * @dataProvider chunkSourceProvider
   */
  public function testChunk($source, $batch_size, $preserve_keys, $expected) {
    $chunked = Traversables::chunk($source, $batch_size, $preserve_keys);
    $this->assertSame($expected, iterator_to_array($chunked));
  }

  /**
   * Provides data for ::testChunk().
   *
   * @return \Generator
   *
   * @see \Drupal\Tests\contentserialize\Unit\TraversablesTest::testChunk()
   */
  public function chunkSourceProvider() {
    // PRESERVE KEYS
    // Test a source is chunked with leftovers.
    yield [
      $this->seven(), 3, TRUE, [
        ['a' => 'A', 'b' => 'B', 'c' => 'C'],
        ['d' => 'D', 'e' => 'E', 'f' => 'F'],
        ['g' => 'G'],
      ],
    ];
    // Test a source is chunked without leftovers.
    yield [
      $this->four(), 2, TRUE, [
        ['a' => 'A', 'b' => 'B'],
        ['c' => 'C', 'd' => 'D'],
      ],
    ];
    // Test with chunk size equal to source length.
    yield [
      $this->four(), 4, TRUE, [
        ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
      ]
    ];
    // Test with chunk size larger than source length.
    yield [
      $this->four(), 6, TRUE, [
        ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
      ]
    ];
    // DON'T PRESERVE KEYS.
    yield [
      $this->seven(), 3, FALSE, [
        ['A', 'B', 'C'],
        ['D', 'E', 'F'],
        ['G'],
      ],
    ];
    // Test a source is chunked without leftovers.
    yield [
      $this->four(), 2, FALSE, [
        ['A', 'B'],
        ['C', 'D'],
      ],
    ];
    // Test with chunk size equal to source length.
    yield [
      $this->four(), 4, FALSE, [
        ['A', 'B', 'C', 'D'],
      ]
    ];
    // Test with chunk size larger than source length.
    yield [
      $this->four(), 6, FALSE, [
        ['A', 'B', 'C', 'D'],
      ]
    ];
  }

  /**
   * @covers ::uniqueByKey
   */
  public function testUniqueByKey() {
    // @todo: PHP 7.x: Use an immediately invoked function expression.
    $source_generator = function () {
      yield 'a' => 'A';
      yield 'b' => 'B';
      yield 'c' => 'C';
      yield 'd' => 'D';
      yield 'c' => 'Z';
      yield 'd' => 'Z';
    };
    $source = $source_generator();
    $expected = [
      'a' => 'A',
      'b' => 'B',
      'c' => 'C',
      'd' => 'D',
    ];
    $unique = Traversables::uniqueByKey($source);
    $unique_array = iterator_to_array($unique);

    $this->assertSame($expected, $unique_array);
    $this->assertSame(4, count($unique_array));
  }

  /**
   * @covers ::filter
   */
  public function testFilterByValue() {
    $filtered = Traversables::filter($this->four(), function ($value) {
      return $value === 'A';
    });
    $this->assertSame(['a' => 'A'], iterator_to_array($filtered));
  }

  /**
   * @covers ::filter
   */
  public function testFilterByKey() {
    $filtered = Traversables::filter($this->four(), function ($key) {
      return $key === 'a';
    }, ARRAY_FILTER_USE_KEY);
    $this->assertSame(['a' => 'A'], iterator_to_array($filtered));
  }

  /**
   * @covers ::filter
   */
  public function testFilterByBoth() {
    $filtered = Traversables::filter($this->four(), function ($value, $key) {
      return $key === 'a' && $value === 'A';
    }, ARRAY_FILTER_USE_BOTH);
    $this->assertSame(['a' => 'A'], iterator_to_array($filtered));
  }

  /**
   * @covers ::merge
   */
  public function testMerge() {
    $merged = Traversables::merge($this->piece1(), $this->piece2(), $this->piece3());
    $expected = [
      'a' => 'A',
      'b' => 'B',
      'c' => 'C',
      'd' => 'D',
      'e' => 'E',
      'f' => 'F',
    ];
    $this->assertSame($expected, iterator_to_array($merged));
  }

  /**
   * Yield two elements for merging with the other pieces.
   *
   * @return \Generator
   *
   * @see \Drupal\Tests\contentserialize\Unit\TraversablesTest::testMerge()
   */
  protected function piece1() {
    yield 'a' => 'A';
    yield 'b' => 'B';
  }

  /**
   * Yield two elements for merging with the other pieces.
   *
   * @return \Generator
   *
   * @see \Drupal\Tests\contentserialize\Unit\TraversablesTest::testMerge()
   */
  protected function piece2() {
    yield 'c' => 'C';
    yield 'd' => 'D';
  }

  /**
   * Yield two elements for merging with the other pieces.
   *
   * @return \Generator
   *
   * @see \Drupal\Tests\contentserialize\Unit\TraversablesTest::testMerge()
   */
  protected function piece3() {
    yield 'e' => 'E';
    yield 'f' => 'F';
  }

  /**
   * Yield four elements following a pattern.
   *
   * @return \Generator
   */
  function four() {
    yield 'a' => 'A';
    yield 'b' => 'B';
    yield 'c' => 'C';
    yield 'd' => 'D';
  }

  /**
   * Yield seven elements following a pattern.
   *
   * @return \Generator
   */
  function seven() {
    yield 'a' => 'A';
    yield 'b' => 'B';
    yield 'c' => 'C';
    yield 'd' => 'D';
    yield 'e' => 'E';
    yield 'f' => 'F';
    yield 'g' => 'G';
  }

}