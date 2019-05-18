<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.05.17
 * Time: 00:34
 */

namespace Drupal\Tests\elastic_search\Unit\Plugin\FieldMapper\FormHelper;

use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\AnalyzerDslProcessor;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\AnalyzerField;
use Drupal\Tests\UnitTestCase;

/**
 * @group elastic_search
 */
class AnalyzerDslProcessorTest extends UnitTestCase {

  /**
   * Test that analyzer dsl is built correctly
   */
  public function testAnalyzerDslProcessor() {

    $trait = new HasTrait();

    $noKey = [
      'i_have'           => FALSE,
      'no_keys'          => TRUE,
      'that_will_please' => 'the trait',
    ];
    $output = $trait->traitProxy($noKey);
    self::assertEquals($noKey, $output);

    $keys = [
      ['analyzer_language_deriver', FALSE],
      ['search_analyzer_language_deriver', FALSE],
      ['search_quote_analyzer_language_deriver', FALSE],
    ];

    $output = $trait->traitProxy($keys[0]);
    self::assertEquals($keys[0], $output);
    $output = $trait->traitProxy($keys[1]);
    self::assertEquals($keys[1], $output);
    $output = $trait->traitProxy($keys[2]);
    self::assertEquals($keys[2], $output);

    $aldTrueKeys = [
      'analyzer_language_deriver'              => TRUE,
      'analyzer'                               => 'en',
      'search_analyzer_language_deriver'       => FALSE,
      'search_quote_analyzer_language_deriver' => FALSE,
    ];

    $result = ['analyzer' => '[elastic_search:language_derived_analyzer]'];

    $output = $trait->traitProxy($aldTrueKeys);
    self::assertEquals($result, $output);

    $everythingTrueKeys = [
      'analyzer_language_deriver'              => TRUE,
      'search_analyzer_language_deriver'       => TRUE,
      'search_quote_analyzer_language_deriver' => TRUE,
    ];

    $result = [
      'analyzer'              => '[elastic_search:language_derived_analyzer]',
      'search_analyzer'       => '[elastic_search:language_derived_analyzer]',
      'search_quote_analyzer' => '[elastic_search:language_derived_analyzer]',
    ];

    $output = $trait->traitProxy($everythingTrueKeys);
    self::assertEquals($result, $output);

    $everythingNoneKeys = [
      'analyzer'              => AnalyzerField::$NONE_IDENTIFIER,
      'search_analyzer'       => AnalyzerField::$NONE_IDENTIFIER,
      'search_quote_analyzer' => AnalyzerField::$NONE_IDENTIFIER,
    ];

    $output = $trait->traitProxy($everythingNoneKeys);
    self::assertEmpty($output);

    $bitOfEverythingKeys = [
      'analyzer_language_deriver'              => FALSE,
      'search_analyzer_language_deriver'       => TRUE,
      'search_quote_analyzer_language_deriver' => FALSE,
      'search_analyzer'                        => 'en',
      'search_quote_analyzer'                  => AnalyzerField::$NONE_IDENTIFIER,
    ];

    $result = [
      'search_analyzer'       => '[elastic_search:language_derived_analyzer]',
    ];

    $output = $trait->traitProxy($bitOfEverythingKeys);
    self::assertEquals($result, $output);

  }

}

class HasTrait {
  use AnalyzerDslProcessor;

  /**
   * @param array $data
   *
   * @return array
   */
  public function traitProxy(array $data): array {
    return $this->buildAnalyzerDsl($data);
  }
}