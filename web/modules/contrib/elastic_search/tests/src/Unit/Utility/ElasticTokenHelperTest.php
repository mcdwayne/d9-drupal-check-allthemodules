<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 10.02.17
 * Time: 13:47
 */

namespace Drupal\Tests\elastic_search\Unit\Utility;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\elastic_search\Utility\ElasticTokenHelper;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Test the token helper
 *
 * @group elastic_search
 */
class ElasticTokenHelperTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test that the proper token is generated and that the output is
   * translatable markup
   */
  public function testTokenHelperInfo() {
    $ti = \Mockery::mock(TranslationInterface::class);
    $info = ElasticTokenHelper::getTokenInfo($ti);
    $this->assertArrayHasKey('tokens', $info);
    $this->assertArrayHasKey('elastic_search', $info['tokens']);
    $this->assertArrayHasKey('language_derived_analyzer',
                             $info['tokens']['elastic_search']);
    $this->assertInstanceOf(TranslatableMarkup::class,
                            $info['tokens']['elastic_search']['language_derived_analyzer'][0]);

  }

  /**
   * Test with an incorrect outer key
   */
  public function testReplaceIncorrectTypeKey() {
    $bubble = \Mockery::mock(BubbleableMetadata::class);
    $replacements = ElasticTokenHelper::doTokenReplacement('banana_cream_pie',
                                                           ['language_derived_analyzer' => '[elastic_search:language_derived_analyzer]'],
                                                           [],
                                                           [],
                                                           $bubble);
    $this->assertEmpty($replacements);
  }

  /**
   * Test with the correct outer but incorrect inner key
   */
  public function testReplaceIncorrectInnerKey() {
    $bubble = \Mockery::mock(BubbleableMetadata::class);
    $replacements = ElasticTokenHelper::doTokenReplacement('elastic_search',
                                                           ['a_dream_of_earth' => '[elastic_search:language_derived_analyzer]'],
                                                           [],
                                                           [],
                                                           $bubble);
    $this->assertEmpty($replacements);
  }

  /**
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage No language key found
   */
  public function testTokenReplacementNoLangKey() {
    $bubble = \Mockery::mock(BubbleableMetadata::class);
    $replacements = ElasticTokenHelper::doTokenReplacement('elastic_search',
                                                           ['language_derived_analyzer' => '[elastic_search:language_derived_analyzer]'],
                                                           [],
                                                           [],
                                                           $bubble);
  }

  /**
   * Test with an invalid language
   */
  public function testTokenReplacementInvalidLangKey() {
    $bubble = \Mockery::mock(BubbleableMetadata::class);
    $replacements = ElasticTokenHelper::doTokenReplacement('elastic_search',
                                                           ['language_derived_analyzer' => '[elastic_search:language_derived_analyzer]'],
                                                           ['lang' => 'robonia'],
                                                           [],
                                                           $bubble);
    $this->assertArrayHasKey('[elastic_search:language_derived_analyzer]',
                             $replacements);
    $this->assertEquals('standard',
                        $replacements['[elastic_search:language_derived_analyzer]']);
  }

  /**
   * Test getting a real langauge key
   * Obviously this test passing depends on the language key not changing
   */
  public function testTokenReplacementValidLangKey() {
    $bubble = \Mockery::mock(BubbleableMetadata::class);
    $replacements = ElasticTokenHelper::doTokenReplacement('elastic_search',
                                                           ['language_derived_analyzer' => '[elastic_search:language_derived_analyzer]'],
                                                           ['lang' => 'en'],
                                                           [],
                                                           $bubble);
    $this->assertArrayHasKey('[elastic_search:language_derived_analyzer]',
                             $replacements);
    $this->assertEquals('english',
                        $replacements['[elastic_search:language_derived_analyzer]']);
  }

}
