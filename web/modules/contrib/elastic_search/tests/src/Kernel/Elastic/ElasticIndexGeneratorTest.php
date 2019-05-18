<?php

namespace Drupal\Tests\elastic_search\Kernel\Elastic;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
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
class ElasticIndexGeneratorTest extends KernelTestBase {

  use MockeryPHPUnitIntegration;

  /**
   * Test a child only index, which will return nothing
   */
  public function testIndexGeneratorChildOnly() {

    $configEntityStorage = \Mockery::mock(ConfigEntityStorageInterface::class);

    $entityStorage = \Mockery::mock(EntityStorageInterface::class);
    //We need to return an array of FieldableEntityMaps from the load multiple
    $map = \Mockery::mock(FieldableEntityMapInterface::class);
    $map->shouldReceive('isChildOnly')->times()->andReturn(TRUE);
    $multLoadResult = [
      'file__file' => $map,
    ];
    $entityStorage->shouldReceive('loadMultipleOverrideFree')
                  ->andReturn($multLoadResult);

    $etm = \Mockery::mock(EntityTypeManager::class);
    $etm->shouldReceive('getStorage')
        ->andReturn($entityStorage, $configEntityStorage);

    $lm = \Mockery::mock(LanguageManagerInterface::class);
    $lm->shouldReceive('getLanguages')
       ->times()
       ->andReturn(['en' => \Mockery::mock(Language::class)]);

    $bi = \Mockery::mock(EntityTypeBundleInfo::class);
    $bi->shouldReceive('getBundleInfo')->andReturn(['file' => ['translatable' => TRUE]]);
    /** @var ElasticIndexGenerator $gen */
    $gen = \Mockery::mock(ElasticIndexGenerator::class, [$etm, $lm, $bi])
                   ->shouldAllowMockingProtectedMethods()
                   ->makePartial();
    //No parameter means load all
    $this->assertEmpty($gen->generate());

  }

  /**
   * Test a child and normal index in an array
   */
  public function testIndexGeneratorChildOnlyMixed() {

    $configEntityStorage = \Mockery::mock(ConfigEntityStorageInterface::class);
    $configEntityStorage->shouldReceive('create')
                        ->times()
                        ->andReturn(new ElasticIndex([], 'elastic_index'));
    $configEntityStorage->shouldReceive('load')->times()->andReturn(NULL);

    //We need to return an array of FieldableEntityMaps from the load multiple
    $map = \Mockery::mock(FieldableEntityMapInterface::class);
    $map->shouldReceive('isChildOnly')->times()->andReturn(TRUE);

    $mapNoChild = \Mockery::mock(FieldableEntityMapInterface::class);
    $mapNoChild->shouldReceive('isChildOnly')->times()->andReturn(FALSE);
    $mapNoChild->shouldReceive('getId')->times()->andReturn('node__whatever');

    $multLoadResult = [
      'file__file'     => $map,
      'node__whatever' => $mapNoChild,
    ];
    $entityStorage = \Mockery::mock(EntityStorageInterface::class);
    $entityStorage->shouldReceive('loadMultipleOverrideFree')
                  ->andReturn($multLoadResult);

    $etm = \Mockery::mock(EntityTypeManager::class);
    $etm->shouldReceive('getStorage')
        ->andReturn($entityStorage, $configEntityStorage);

    $language = \Mockery::mock(Language::class);
    $language->shouldReceive('getId')->andReturn('en');

    $lm = \Mockery::mock(LanguageManagerInterface::class);
    $lm->shouldReceive('getLanguages')
       ->times()
       ->andReturn(['en' => $language]);

    $bi = \Mockery::mock(EntityTypeBundleInfo::class);
    $bi->shouldReceive('getBundleInfo')->andReturn(['whatever' => ['translatable' => TRUE]]);
    $bi->shouldReceive('getBundleInfo')->andReturn(['file' => ['translatable' => TRUE]]);

    /** @var ElasticIndexGenerator $gen */
    $gen = \Mockery::mock(ElasticIndexGenerator::class, [$etm, $lm, $bi])
                   ->shouldAllowMockingProtectedMethods()
                   ->makePartial();
    //No parameter means load all
    $output = $gen->generate();
    $this->assertCount(1, $output);
    /** @var ElasticIndex $index */
    $index = reset($output);
    $this->assertInstanceOf(ElasticIndex::class, $index);

    $this->assertEquals('node__whatever', $index->getIndexId());
    $this->assertEquals('en', $index->getIndexLanguage());
    $this->assertEquals('_', $index->getSeparator());
    $this->assertEquals('node__whatever_en', $index->getIndexName());
  }

  /**
   * Test with loading an entity that exists.
   * In reality the id would not change, but for the purpose of checking that
   * we are really returning the loaded entity we alter it in the test mock
   */
  public function testIndexGeneratorExists() {

    $configEntityStorage = \Mockery::mock(ConfigEntityStorageInterface::class);
    $configEntityStorage->shouldReceive('create')
                        ->times()
                        ->andReturn(new ElasticIndex([], 'elastic_index'));
    $configEntityStorage->shouldReceive('load')
                        ->times()
                        ->andReturn(new ElasticIndex([
                                                       'indexId'       => 'node__whatever__loaded',
                                                       'indexLanguage' => 'en',
                                                     ], 'elastic_index'));

    $entityStorage = \Mockery::mock(EntityStorageInterface::class);
    //We need to return an array of FieldableEntityMaps from the load multiple
    $map = \Mockery::mock(FieldableEntityMapInterface::class);
    $map->shouldReceive('isChildOnly')->times()->andReturn(TRUE);

    $mapNoChild = \Mockery::mock(FieldableEntityMapInterface::class);
    $mapNoChild->shouldReceive('isChildOnly')->times()->andReturn(FALSE);
    $mapNoChild->shouldReceive('getId')->times()->andReturn('node__whatever');

    $multLoadResult = [
      'file__file'     => $map,
      'node__whatever' => $mapNoChild,
    ];
    $entityStorage->shouldReceive('loadMultipleOverrideFree')
                  ->andReturn($multLoadResult);

    $etm = \Mockery::mock(EntityTypeManager::class);
    $etm->shouldReceive('getStorage')
        ->andReturn($entityStorage, $configEntityStorage);

    $language = \Mockery::mock(Language::class);
    $language->shouldReceive('getId')->andReturn('en');

    $lm = \Mockery::mock(LanguageManagerInterface::class);
    $lm->shouldReceive('getLanguages')
       ->times()
       ->andReturn(['en' => $language]);

    $bi = \Mockery::mock(EntityTypeBundleInfo::class);
    $bi->shouldReceive('getBundleInfo')->andReturn(['whatever' => [ 'translatable' => TRUE ]]);
    $bi->shouldReceive('getBundleInfo')->andReturn(['file' => [ 'translatable' => TRUE ]]);
    /** @var ElasticIndexGenerator $gen */
    $gen = \Mockery::mock(ElasticIndexGenerator::class, [$etm, $lm, $bi])
                   ->shouldAllowMockingProtectedMethods()
                   ->makePartial();
    //No parameter means load all
    $output = $gen->generate();
    //as the entity exists already the generator will not return it
    $this->assertCount(0, $output);
  }

  /**
   * Test with loading an entity that exists.
   * In reality the id would not change, but for the purpose of checking that
   * we are really returning the loaded entity we alter it in the test mock
   *
   * @expectedException \Drupal\elastic_search\Exception\IndexGeneratorBundleNotFoundException
   * @expectedExceptionMessage Could not find bundle type: whatever on entity type: node
   */
  public function testIndexNoBundle() {

    $configEntityStorage = \Mockery::mock(ConfigEntityStorageInterface::class);
    $configEntityStorage->shouldReceive('create')
                        ->times()
                        ->andReturn(new ElasticIndex([], 'elastic_index'));
    $configEntityStorage->shouldReceive('load')
                        ->times()
                        ->andReturn(new ElasticIndex([
                                                       'indexId'       => 'node__whatever__loaded',
                                                       'indexLanguage' => 'en',
                                                     ], 'elastic_index'));

    $entityStorage = \Mockery::mock(EntityStorageInterface::class);
    //We need to return an array of FieldableEntityMaps from the load multiple
    $map = \Mockery::mock(FieldableEntityMapInterface::class);
    $map->shouldReceive('isChildOnly')->times()->andReturn(TRUE);

    $mapNoChild = \Mockery::mock(FieldableEntityMapInterface::class);
    $mapNoChild->shouldReceive('isChildOnly')->times()->andReturn(FALSE);
    $mapNoChild->shouldReceive('getId')->times()->andReturn('node__whatever');

    $multLoadResult = [
      'file__file'     => $map,
      'node__whatever' => $mapNoChild,
    ];
    $entityStorage->shouldReceive('loadMultipleOverrideFree')
                  ->andReturn($multLoadResult);

    $etm = \Mockery::mock(EntityTypeManager::class);
    $etm->shouldReceive('getStorage')
        ->andReturn($entityStorage, $configEntityStorage);

    $language = \Mockery::mock(Language::class);
    $language->shouldReceive('getId')->andReturn('en');

    $lm = \Mockery::mock(LanguageManagerInterface::class);
    $lm->shouldReceive('getLanguages')
       ->times()
       ->andReturn(['en' => $language]);

    $bi = \Mockery::mock(EntityTypeBundleInfo::class);
    $bi->shouldReceive('getBundleInfo')->andReturn(['something_else' => [ 'translatable' => TRUE ]]);
    /** @var ElasticIndexGenerator $gen */
    $gen = \Mockery::mock(ElasticIndexGenerator::class, [$etm, $lm, $bi])
                   ->shouldAllowMockingProtectedMethods()
                   ->makePartial();
    //No parameter means load all
    $gen->generate();
  }

  /**
   * Test with loading an entity that exists.
   * In reality the id would not change, but for the purpose of checking that
   * we are really returning the loaded entity we alter it in the test mock
   */
  public function testIndexGeneratorNonTranslate() {

    $configEntityStorage = \Mockery::mock(ConfigEntityStorageInterface::class);
    $configEntityStorage->shouldReceive('create')
                        ->times()
                        ->andReturn(new ElasticIndex([], 'elastic_index'));
    $configEntityStorage->shouldReceive('load')
                        ->times()
                        ->andReturn(NULL);

    $entityStorage = \Mockery::mock(EntityStorageInterface::class);
    //We need to return an array of FieldableEntityMaps from the load multiple
    $mapNoChild = \Mockery::mock(FieldableEntityMapInterface::class);
    $mapNoChild->shouldReceive('isChildOnly')->times()->andReturn(FALSE);
    $mapNoChild->shouldReceive('getId')->times()->andReturn('node__whatever');

    $multLoadResult = [
      'node__whatever' => $mapNoChild,
    ];
    $entityStorage->shouldReceive('loadMultipleOverrideFree')
                  ->andReturn($multLoadResult);

    $etm = \Mockery::mock(EntityTypeManager::class);
    $etm->shouldReceive('getStorage')
        ->andReturn($entityStorage, $configEntityStorage);

    $language = \Mockery::mock(Language::class);
    $language->shouldReceive('getId')->andReturn('en');

    $languageD = \Mockery::mock(Language::class);
    $languageD->shouldReceive('getId')->andReturn('de');

    $lm = \Mockery::mock(LanguageManagerInterface::class);
    $lm->shouldReceive('getLanguages')
       ->once()
       ->andReturn(['en' => $language, 'de' > $languageD ]);
    $lm->shouldReceive('getDefaultLanguage')
       ->times(1)
       ->andReturn($languageD);

    $bi = \Mockery::mock(EntityTypeBundleInfo::class);
    $bi->shouldReceive('getBundleInfo')->andReturn(['whatever' => [ 'translatable' => FALSE ]]);
    /** @var ElasticIndexGenerator $gen */
    $gen = \Mockery::mock(ElasticIndexGenerator::class, [$etm, $lm, $bi])
                   ->shouldAllowMockingProtectedMethods()
                   ->makePartial();
    //No parameter means load all
    $output = $gen->generate();
    $this->assertCount(1, $output);
    /** @var ElasticIndex $index */
    $index = reset($output);
    $this->assertInstanceOf(ElasticIndex::class, $index);

    $this->assertEquals('node__whatever', $index->getIndexId());
    $this->assertEquals('de', $index->getIndexLanguage());
    $this->assertEquals('_', $index->getSeparator());
    $this->assertEquals('node__whatever_de', $index->getIndexName());
  }

}
