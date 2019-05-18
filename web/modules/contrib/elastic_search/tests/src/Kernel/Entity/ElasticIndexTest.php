<?php

namespace Drupal\Tests\elastic_search\Kernel\Elastic;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elastic_search\Elastic\ElasticIndexGenerator;
use Drupal\elastic_search\Entity\ElasticIndex;
use Drupal\elastic_search\Entity\FieldableEntityMapInterface;
use Drupal\KernelTests\KernelTestBase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * ElasticIndexGeneratorTest
 *
 * @group elastic_search
 */
class ElasticIndexTest extends KernelTestBase {

  use MockeryPHPUnitIntegration;

  /**
   * @var array
   */
  public static $modules = [
    'elastic_search',
  ];

  /**
   * Test a child only index, which will return nothing
   */
  public function testIndexNameNoPrefix() {
    //Prove that a config prefix works
    /** @var \Drupal\elastic_search\Entity\ElasticIndexInterface $index */
    $index = ElasticIndex::create([]);
    $indexId = 'test_id';
    $indexLang = 'en';
    $indexSeperator = '.';
    $index->setIndexId($indexId);
    $index->setIndexLanguage($indexLang);
    $index->setSeparator($indexSeperator);
    $indexName = $index->getIndexName();
    self::assertEquals($indexId . $indexSeperator . $indexLang, $indexName);
  }

  /**
   * Test a child only index, which will return nothing
   */
  public function testIndexNamePrefix() {

    $prefix = 'prefix_for_death';
    $config = \Drupal::configFactory()->getEditable('elastic_search.server');
    $config->set('index_prefix', 'prefix_for_death');
    $config->save();

    //Prove that a config prefix works
    /** @var \Drupal\elastic_search\Entity\ElasticIndexInterface $index */
    $index = ElasticIndex::create([]);
    $indexId = 'test_id';
    $indexLang = 'en';
    $indexSeperator = '.';
    $index->setIndexId($indexId);
    $index->setIndexLanguage($indexLang);
    $index->setSeparator($indexSeperator);
    $indexName = $index->getIndexName();
    self::assertEquals($prefix . $indexSeperator . $indexId . $indexSeperator . $indexLang, $indexName);
  }

}
