<?php

namespace Drupal\Tests\inmail\Unit;

use Drupal\inmail\DSNStatus;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests the DSN status class.
 *
 * @coversDefaultClass \Drupal\inmail\DSNStatus
 *
 * @group inmail
 */
class DSNStatusTest extends UnitTestCase {

  /**
   * Tests the constructor for invalid codes.
   *
   * @covers ::__construct
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider provideInvalidCodes
   */
  public function testConstructInvalid($class, $subject, $detail) {
    new DSNStatus($class, $subject, $detail);
  }

  /**
   * Tests the parse method for valid codes.
   *
   * @covers ::parse
   *
   * @dataProvider provideValidCodes
   */
  public function testParse($class, $subject, $detail) {
    DSNStatus::parse("$class.$subject.$detail");
  }

  /**
   * Tests the parse method for invalid codes.
   *
   * @covers ::parse
   *
   * @expectedException \InvalidArgumentException
   *
   * @dataProvider provideInvalidCodes
   */
  public function testParseInvalid($class, $subject, $detail) {
    DSNStatus::parse("$class.$subject.$detail");
  }

  /**
   * Tests the getCode method.
   *
   * @covers ::getCode
   *
   * @dataProvider provideValidCodes
   */
  public function testGetCode($class, $subject, $detail) {
    $status = new DSNStatus($class, $subject, $detail);
    $this->assertEquals("$class.$subject.$detail", $status->getCode());
  }

  /**
   * Tests the isSuccess method.
   *
   * @covers ::isSuccess
   *
   * @dataProvider provideValidCodes
   */
  public function testIsSuccess($class, $subject, $detail) {
    $status = new DSNStatus($class, $subject, $detail);
    $this->assertEquals($class == 2, $status->isSuccess());
  }

  /**
   * Tests the isPermanentFailure method.
   *
   * @covers ::isPermanentFailure
   *
   * @dataProvider provideValidCodes
   */
  public function testIsPermanentFailure($class, $subject, $detail) {
    $status = new DSNStatus($class, $subject, $detail);
    $this->assertEquals($class == 5, $status->isPermanentFailure());
  }

  /**
   * Tests the isTransientFailure method.
   *
   * @covers ::isTransientFailure
   *
   * @dataProvider provideValidCodes
   */
  public function testIsTransientFailure($class, $subject, $detail) {
    $status = new DSNStatus($class, $subject, $detail);
    $this->assertEquals($class == 4, $status->isTransientFailure());
  }

  /**
   * Tests label methods for known codes.
   *
   * @covers ::getLabel
   * @covers ::getClassLabel
   * @covers ::getDetailLabel
   *
   * @dataProvider provideKnownCodes
   */
  public function getLabelKnown($class, $subject, $detail) {
    $status = new DSNStatus($class, $subject, $detail);
    $this->assertTrue(strlen($status->getClassLabel()) > 0);
    $this->assertTrue(strlen($status->getDetailLabel()) > 0);
    $this->assertEquals($status->getClassLabel() . ': ' . $status->getDetailLabel(), $status->getLabel());
  }

  /**
   * Tests label methods for codes with known subject sub-code (second number).
   *
   * @covers ::getLabel
   * @covers ::getClassLabel
   * @covers ::getDetailLabel
   */
  public function getLabelKnownSubject() {
    // Not bothering to write a provider method for this.
    $classes = [2, 4, 5];
    $subjects = range(0, 7);
    // Just set the detail to something valid (0 < x < 999) but unrecognized.
    $detail = 162;

    foreach ($classes as $class) {
      foreach ($subjects as $subject) {
        $status = new DSNStatus($class, $subject, $detail);
        $this->assertTrue(strlen($status->getClassLabel()) > 0);
        $this->assertTrue(strlen($status->getDetailLabel()) > 0);
        $this->assertEquals($status->getClassLabel() . ': ' . $status->getDetailLabel(), $status->getLabel());
      }
    }
  }

  /**
   * Tests label methods for valid but unknown codes.
   *
   * @covers ::getLabel
   * @covers ::getClassLabel
   * @covers ::getDetailLabel
   *
   * @dataProvider provideOtherCodes
   */
  public function getLabelOther($class, $subject, $detail) {
    $status = new DSNStatus($class, $subject, $detail);
    $this->assertTrue(strlen($status->getClassLabel()) > 0);
    $this->assertNull($status->getDetailLabel());
    $this->assertEquals($status->getClassLabel(), $status->getLabel());
  }

  /**
   * Provides the DSN status codes defined in RFC 3463.
   *
   * These all have labels assigned to them in DSNStatus.
   *
   * @return array
   *   An array where each element is a three-element array of integers and
   *   represents a status code.
   */
  public function provideKnownCodes() {
    $max_detail_per_subject = [0, 8, 4, 5, 7, 5, 5, 7];
    $codes = [];
    foreach ([2, 4, 5] as $class) {
      foreach (range(0, 7) as $subject) {
        foreach (range(0, $max_detail_per_subject[$subject]) as $detail) {
          $codes[] = [$class, $subject, $detail];
        }
      }
    }
    return $codes;
  }

  /**
   * Provides some DSN status codes that are valid but not defined in RFC 3463.
   *
   * @return array
   *   An array where each element is a three-element array of integers and
   *   represents a status code.
   */
  public function provideOtherCodes() {
    return [
      // Excessive subject part.
      [4, 8, 0],
      // Excessive detail part per subject; 1 more than the greatest defined.
      [4, 0, 1],
      [4, 1, 9],
      [4, 2, 5],
      [4, 3, 6],
      [4, 4, 8],
      [4, 5, 6],
      [4, 6, 6],
      [4, 7, 8],
    ];
  }

  /**
   * Provides the DSN status codes defined in 3463 and a few other valid codes.
   *
   * @return array
   *   An array where each element is a three-element array of integers and
   *   represents a status code.
   */
  public function provideValidCodes() {
    return array_merge($this->provideKnownCodes(), $this->provideOtherCodes());
  }

  /**
   * Provides some invalid DSN status codes.
   *
   * @return array
   *   An array where each element is a three-element array of integers.
   */
  public function provideInvalidCodes() {
    return [
      // Invalid class part.
      [1, 0, 0],
      [3, 0, 0],
      [6, 0, 0],
      // Invalid subject/detail parts.
      [4, -1, 0],
      [4, 1000, 0],
      [4, 0, -1],
      [4, 0, 1000],
    ];
  }

}
