<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 11.02.17
 * Time: 00:39
 */

namespace Drupal\Tests\elastic_search\Unit\Mapping;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\elastic_search\Mapping\Cartographer;
use Drupal\elastic_search\Mapping\ElasticMappingDslGenerator;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * ElasticMappingDslGeneratorTest
 *
 * @group elastic_search
 */
class ElasticMappingDslGeneratorTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * @param array $multLoadResult
   * @param mixed $cartographer
   *
   * @return \Drupal\elastic_search\Mapping\ElasticMappingDslGenerator
   */
  private function basicBuild(array $multLoadResult, $cartographer) {

    $entityStorage = \Mockery::mock(EntityStorageInterface::class);
    $entityStorage->shouldReceive('loadMultipleOverrideFree')
                  ->andReturn($multLoadResult);

    $etm = \Mockery::mock(EntityTypeManager::class);
    $etm->shouldReceive('getStorage')->times()->andReturn($entityStorage);

    $lmi = \Mockery::mock(LanguageManagerInterface::class);
    $lmi->shouldReceive('getLanguages')->times()->andReturn([]);

    $token = \Mockery::mock(Token::class);
    $token->shouldReceive('replace')
          ->times()
          ->andReturnUsing(function ($input) {
            return $input;
          });
    return new ElasticMappingDslGenerator($etm, $lmi, $cartographer, $token);

  }

  /**
   * @return array
   */
  private function mapLoadResults() {
    $map = \Mockery::mock(FieldableEntityMapInterface::class);
    $map->shouldReceive('isChildOnly')->times()->andReturn(TRUE);
    $map->shouldReceive('id')->times()->andReturn('file__file');

    $mapNoChild = \Mockery::mock(FieldableEntityMapInterface::class);
    $mapNoChild->shouldReceive('isChildOnly')->times()->andReturn(FALSE);
    $mapNoChild->shouldReceive('id')->times()->andReturn('node__whatever');

    return [
      'file__file'     => $map,
      'node__whatever' => $mapNoChild,
    ];

  }

  /**
   * @param array $returnVals
   *
   * @return \Mockery\MockInterface
   */
  private function getCartographerMock(array $returnVals = [['elastic_mapping' => 'mock']]) {
    $cartographer = \Mockery::mock(Cartographer::class);
    $cartographer->shouldReceive('makeElasticMapping')
                 ->andReturnValues($returnVals);
    return $cartographer;
  }

  /**
   * @expectedException \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function testEmptyMaps() {
    $gen = $this->basicBuild([], $this->getCartographerMock());
    $generated = $gen->generate();
  }

  /**
   * Test mapping with default values.
   */
  public function testElasticMappingDslGeneratorDefaults() {

    $gen = $this->basicBuild($this->mapLoadResults(),
                             $this->getCartographerMock());
    $this->assertFalse($gen->hadErrors());
    $this->assertEmpty($gen->getErrors());
    $this->assertEmpty($gen->triggerTokenReplacement([], 'en'));
    $generated = $gen->generate();
    $this->assertCount(2, $generated);
  }

  /**
   * This does prove that it handles errors, but the error is not what you
   * would expect!
   */
  public function testCartographerExceptionHandling() {
    $gen = $this->basicBuild($this->mapLoadResults(),
                             $this->getCartographerMock([
                                                          ['elastic_mapping' => 'mock'],
                                                          function () {
                                                            throw new DslTestException('FAIL!');
                                                          },
                                                        ]));
    $generated = $gen->generate();
    $this->assertTrue($gen->hadErrors());
    $errors = $gen->getErrors();
    $this->assertCount(1, $errors);
    $this->assertCount(1, $generated);
  }

  /**
   * Test with a token replacement occuring
   */
  public function testTokenReplacement() {

    $gen = $this->basicBuild($this->mapLoadResults(),
                             $this->getCartographerMock());
    $language = \Mockery::mock(LanguageInterface::class);
    $language->shouldReceive('getId')->times()->andReturn('en');
    $replacement = $gen->triggerTokenReplacement($this->getTokenReplacementData(),
                                                 $language);
    //We dont actually do a token replacement, since that is not done by the dsl generator class directly
    //We only tests the logic around that, and therefore return an unaltered array
    $this->assertArraySubset($this->getTokenReplacementData(), $replacement);
  }

  /**
   * Test with a translatable string
   */
  public function testTokenReplacementLanguageString() {

    $gen = $this->basicBuild($this->mapLoadResults(),
                             $this->getCartographerMock());
    $replacement = $gen->triggerTokenReplacement($this->getTokenReplacementData(),
                                                 'en');
    $this->assertArraySubset($this->getTokenReplacementData(), $replacement);
  }

  /**
   * @return array
   */
  private function getTokenReplacementData() {
    return [
      0 =>
        [
          'mappings' =>
            [
              'file__file' =>
                [
                  'properties' =>
                    [
                      'filename' =>
                        [
                          'type'                  => 'text',
                          'analyzer'              => '[elastic_search:language_derived_analyzer]',
                          'boost'                 => 0,
                          'eager_global_ordinals' => FALSE,
                          'fielddata'             => FALSE,
                          'include_in_all'        => TRUE,
                          'index'                 => TRUE,
                          'index_options'         => 'docs',
                          'norms'                 => FALSE,
                          'search_analyzer'       => '[elastic_search:language_derived_analyzer]',
                          'search_quote_analyzer' => '[elastic_search:language_derived_analyzer]',
                          'similarity'            => 'classic',
                          'store'                 => FALSE,
                          'term_vector'           => 'no',
                        ],
                    ],
                ],
            ],
        ],
    ];
  }

}

/**
 * Class DslTestException
 *
 * @package Drupal\Tests\elastic_search\Unit\Elastic
 */
class DslTestException extends \Exception {
}
