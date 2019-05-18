<?php

namespace Drupal\Tests\gathercontent\Kernel;

use Drupal\gathercontent_test\MockData;
use Drupal\migrate_plus\Entity\Migration;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigCreatorTest.
 *
 * @package Drupal\Tests\gathercontent\Kernel
 */
class ConfigCreatorTest extends GcMigrateTestBase {

  const CONFIG_NAMES_CONFIG_CREATE_TEST = [
    '821317' => [
      'migrate_plus.migration.86701_821317_node_article_tab1502959217871' => 'test1.1.yml',
      'migrate_plus.migration.86701_821317_node_article_tab1503046938794' => 'test1.2.yml',
    ],
    '819462' => [
      'migrate_plus.migration.86701_819462_node_simple_test_type_tab1502870979013' => 'test1.1.yml',
      'migrate_plus.migration.86701_819462_node_simple_test_type_tab1503302417527' => 'test1.2.yml',
    ],
  ];

  const CONFIG_KEYS_TO_CHECK = [
    'langcode',
    'status',
    'dependencies',
    'id',
    'label',
    'source',
    'process',
    'destination',
    'migration_dependencies',
    'langcode',
  ];

  public function testConfigCreate() {
    foreach (self::CONFIG_NAMES_CONFIG_CREATE_TEST as $templateId => $testFiles) {
      /** @var \Drupal\gathercontent\Entity\MappingInterface $mapping */
      $mapping = MockData::getSpecificMapping($templateId);
      $mappingData = unserialize($mapping->getData());

      /** @var \Drupal\gathercontent\MigrationDefinitionCreator $creator */
      $creator = \Drupal::service('gathercontent.migration_creator');
      $creator
        ->setMapping($mapping)
        ->setMappingData($mappingData)
        ->createMigrationDefinition();
      $configFactory = \Drupal::configFactory();

      foreach ($testFiles as $configName => $testFile) {
        $configCreatedByService = $configFactory->getEditable($configName);
        $testYml = file_get_contents(DRUPAL_ROOT . "/modules/custom/gathercontent/tests/modules/gathercontent_test/test_definition/$templateId/" . $testFile);
        if (!$testYml) {
          continue;
        }
        $parsedYml = Yaml::parse($testYml);
        $expected = Migration::create($parsedYml);
        foreach (self::CONFIG_KEYS_TO_CHECK as $key) {
          $actual = $configCreatedByService->get($key);
          self::assertEquals($expected->get($key), $actual, "Failed assertion template: $templateId in file: $testFile at key: $key");
        }
      }
    }
  }

}
