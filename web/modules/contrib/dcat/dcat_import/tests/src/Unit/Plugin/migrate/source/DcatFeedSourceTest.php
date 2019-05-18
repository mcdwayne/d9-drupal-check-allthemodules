<?php

namespace Drupal\Tests\dcat_import\Unit\Plugin\migrate\source;

use Drupal\Tests\UnitTestCase;
use Drupal\dcat_import\Plugin\migrate\source\DcatFeedSource;
use EasyRdf_Resource;
use EasyRdf_Literal;
use EasyRdf_Literal_Integer;
use EasyRdf_Literal_DateTime;
use DateTime;

/**
 * @coversDefaultClass \Drupal\dcat_import\Plugin\migrate\source\DcatFeedSource
 * @group dcat_import
 */
class DcatFeedSourceTest extends UnitTestCase {

  /**
   * The response policy under test.
   *
   * @var DcatFeedSource;
   */
  protected $source;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->source = $this->getMockBuilder('Drupal\dcat_import\Plugin\migrate\source\DcatFeedSource')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
  }

  /**
   * Test unifyReturnValue().
   *
   * @dataProvider providerUnifyReturnValue
   */
  public function testUnifyReturnValue($expected_result, $value) {
    $this->assertSame($expected_result, $this->source->unifyReturnValue($value));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerUnifyReturnValue() {
    return [
      [NULL, NULL],
      [NULL, []],
      ['a', ['a']],
      [['a', 'b'], ['a', 'b']],
    ];
  }

  /**
   * Test getSingleValue().
   *
   * @dataProvider providerGetSingleValue
   */
  public function testGetSingleValue($expected_result, $value) {
    $this->assertSame($expected_result, $this->source->getSingleValue($value));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerGetSingleValue() {
    $resource = new EasyRdf_Resource('http://example.com');
    $literal = new EasyRdf_Literal('abcde');
    $literal_integer = new EasyRdf_Literal_Integer(9);
    $date = new DateTime();
    $literal_date = new EasyRdf_Literal_DateTime($date);

    return [
      ['http://example.com', $resource],
      ['abcde', $literal],
      [9, $literal_integer],
      [$date->format('c'), $literal_date],
    ];
  }

  /**
   * Test stripMailto().
   *
   * @dataProvider providerStripMailto
   */
  public function testStripMailto($expected_result, $value) {
    $this->assertSame($expected_result, $this->source->stripMailto($value));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerStripMailto() {
    return [
      ['', ''],
      ['me@example.com', 'me@example.com'],
      ['me@example.com', 'mailto:me@example.com'],
      ['me@example.mailto:com', 'me@example.mailto:com'],
      ['mailto:me@example.com', 'mailto:mailto:me@example.com'],
    ];
  }

}
