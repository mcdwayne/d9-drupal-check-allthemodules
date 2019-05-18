<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 10.02.17
 * Time: 00:23
 */

namespace Drupal\Tests\elastic_search\Unit\Utility;

use Drupal\elastic_search\Utility\DrupalLangCodeToElasticAnalyzer;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Class IdDetailsTest
 *
 * @group elastic_search
 */
class DrupalLangCodeToElasticAnalyzerTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test getting an analyzer
   */
  public function testGetLanguageAnalyzer() {

    $obj = new DrupalLangCodeToElasticAnalyzer();
    $r = new \ReflectionObject($obj);
    $p = $r->getProperty('language_analyzers');
    $p->setAccessible(TRUE);
    $languages = $p->getValue($obj);
    foreach ($languages as $id => $language) {
      $result = DrupalLangCodeToElasticAnalyzer::getLanguageAnalyzer($id);
      $this->assertEquals($language, $result);
    }
  }

  /**
   * Test getting an unknown analyzer, whicch should return a standard analyzer instead
   */
  public function testGetUnknownAnalyzer() {
    $result = DrupalLangCodeToElasticAnalyzer::getLanguageAnalyzer('random_nonsense');
    $this->assertEquals('standard', $result);
  }

}
