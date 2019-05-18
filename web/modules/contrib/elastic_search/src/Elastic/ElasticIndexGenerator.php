<?php

namespace Drupal\elastic_search\Elastic;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\elastic_search\Entity\ElasticIndex;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Drupal\elastic_search\Exception\IndexGeneratorBundleNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElasticIndexGenerator
 *
 * @package Drupal\elastic_search\Elastic
 */
class ElasticIndexGenerator implements ContainerInjectionInterface {

  /**
   * @var EntityTypeManager
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * @var array
   */
  private $errors = [];

  /**
   * ElasticMappingDslGenerator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager          $manager
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo       $bundleInfo
   */
  public function __construct(EntityTypeManager $manager,
                              LanguageManagerInterface $languageManager,
                              EntityTypeBundleInfo $bundleInfo) {
    $this->manager = $manager;
    $this->languageManager = $languageManager;
    $this->bundleInfo = $bundleInfo;
  }

  /**
   * @inheritDoc
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'),
                      $container->get('language_manager'),
                      $container->get('entity_type.bundle.info'));
  }

  /**
   * Generates a set of indices
   * If an index exists already it will not be returned as it does not need to be generated and the purpose of this call
   * is to create new objects to be saved
   *
   * @param array $mapIds
   *
   * @return array
   *
   * @throws \Drupal\elastic_search\Exception\IndexGeneratorBundleNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function generate(array $mapIds = []) {

    //If indices is empty then we do everything
    if (empty($mapIds)) {
      $mapIds = NULL;
    }
    /** @var FieldableEntityMap[] $maps */
    $maps = $this->manager->getStorage('fieldable_entity_map')
                          ->loadMultipleOverrideFree($mapIds);
    $indexStorage = $this->manager->getStorage('elastic_index');

    $allLanguages = $this->languageManager->getLanguages();

    $output = [];
    foreach ($maps as $map) {

      if ($map->isChildOnly()) {
        //Child only mappings do not get their own index
        continue;
      }

      $class = FieldableEntityMap::getEntityAndBundle($map->getId());
      $bi = $this->bundleInfo->getBundleInfo($class['entity']);
      if (!array_key_exists($class['bundle'], $bi)) {
        throw new IndexGeneratorBundleNotFoundException('Could not find bundle type: ' . $class['bundle'] .
                                                        ' on entity type: ' . $class['entity']);
      }

      if ($bi[$class['bundle']]['translatable'] === TRUE) {
        $langs = $allLanguages;
      } else {
        $langs = [$this->languageManager->getDefaultLanguage()];
      }

      foreach ($langs as $lang) {
        /** @var ElasticIndex $index */
        $index = $indexStorage->create();
        $index->setIndexId($map->getId());
        $index->setSeparator('_');
        $index->setIndexLanguage($lang->getId());

        $in = ElasticIndex::buildIndexName($class['entity'], $class['bundle'],$lang->getId());
        if (!$indexStorage->load($in)) {
          $index->setMappingEntityId($map->getId());
          $index->setNeedsUpdate();
          $output[$index->getIndexName()] = $index;
        }
      }

    }
    return $output;
  }

}