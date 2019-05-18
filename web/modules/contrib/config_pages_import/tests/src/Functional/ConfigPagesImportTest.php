<?php

namespace Drupal\Tests\config_pages_import\Functional;


use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\config_pages_import\ConfigPagesImport;
use Drupal\config_pages_import\Exception\ConfigPagesImportException;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Class ConfigPagesImportTest
 *
 * @group config_pages_import
 *
 * @package Drupal\Tests\config_pages_import\Functional
 */
class ConfigPagesImportTest extends BrowserTestBase
{
  public static $modules = [
    'serialization',
    'config_pages',
    'config_pages_import',
  ];

  /**
   * @group config_pages_import
   */
  public function testImportFromModule()
  {
    $import = \Drupal::service('config_pages_import');
    $import->importFromModule('config_pages_import');

    $storage = \Drupal::entityTypeManager()->getStorage('config_pages_type');

    $configPagesType = $storage->load('config_pages_import_test_simple');
    $this->assertInstanceOf(ConfigPagesType::class, $configPagesType);

    $configPagesType = $storage->load('config_pages_import_test_fd');
    $this->assertInstanceOf(ConfigPagesType::class, $configPagesType);
  }

  /**
   * @group config_pages_import
   */
  public function testImportFromMissedModule()
  {
    $this->expectException(ConfigPagesImportException::class);

    $import = \Drupal::service('config_pages_import');
    $import->importFromModule('config_pages_import_weird');
  }

  /**
   * Test simple config entity
   *
   * @group config_pages_import
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testImportSimple()
  {
    $testConfigEntity = 'config_pages_import_test_simple';

    $this->import($testConfigEntity);
    $storage = \Drupal::entityTypeManager()->getStorage('config_pages_type');
    $configPagesType = $storage->load($testConfigEntity);

    $this->assertInstanceOf(ConfigPagesType::class, $configPagesType);

    $fieldStorage = FieldStorageConfig::loadByName(ConfigPagesImport::CONFIG_PAGES_MODULE, 'test_field_1');
    $this->assertNotEmpty($fieldStorage);

    $field = FieldConfig::loadByName(ConfigPagesImport::CONFIG_PAGES_MODULE, $testConfigEntity, 'test_field_1');
    $this->assertNotEmpty($field);

    $formDisplay = EntityFormDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.' . $configPagesType->id() . '.default');
    $this->assertNotEmpty($formDisplay);

    $viedDisplay = EntityViewDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.' . $configPagesType->id() . '.default');
    $this->assertNotEmpty($viedDisplay);
  }

  /**
   * @group config_pages_import
   */
  public function testImportFormDisplay()
  {
    $testConfigEntity = 'config_pages_import_test_fd';

    $configPagesType = $this->import($testConfigEntity);

    $formDisplay = EntityFormDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.' . $configPagesType->id() . '.default');
    $field = $formDisplay->getComponent('test_field_with_form_display_1');
    $this->assertEquals('string_textfield', $field['type']);
    $this->assertEquals(12, $field['settings']['size']);
    $this->assertEquals('Placeholder Text', $field['settings']['placeholder']);
  }

  /**
   * @group config_pages_import
   */
  public function testImportViewDisplay()
  {
    $testConfigEntity = 'config_pages_import_test_vd';

    $configPagesType = $this->import($testConfigEntity);

    $defaultViewDisplay = EntityViewDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.' . $configPagesType->id() . '.default');
    $this->assertNotEmpty($defaultViewDisplay);

    $fullViewDisplay = EntityViewDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.' . $configPagesType->id() . '.full');
    $this->assertNotEmpty($fullViewDisplay);
  }

  /**
   * Test config entity with values
   *
   * @group config_pages_import
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testImportWithValues()
  {
    $testConfigEntity = 'config_pages_import_test_values';

    $configPagesType = $this->import($testConfigEntity);

    $storage = \Drupal::entityTypeManager()->getStorage('config_pages');
    $entity = $storage->load($testConfigEntity);

    $this->assertInstanceOf(ConfigPages::class, $entity);
    $this->assertEquals('Test Value', $entity->get('test_field_1')->value);
  }

  /**
   * @group config_pages_import
   */
  public function testImportFailException()
  {
    $testConfigEntity = 'config_pages_import_test_fail';

    $this->expectException(ConfigPagesImportException::class);

    $this->import($testConfigEntity);
  }

  /**
   * @group config_pages_import
   */
  public function testCleaningAfterException()
  {
    $testConfigEntity = 'config_pages_import_test_fail';
    $configPagesType = NULL;

    try {
      $configPagesType = $this->import($testConfigEntity);
    } catch (ConfigPagesImportException $e) {

    }

    $this->assertEmpty($configPagesType);

    $formDisplay = EntityFormDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.config_pages_import_test_fail.default');
    $this->assertEmpty($formDisplay);

    $defaultViewDisplay = EntityViewDisplay::load(ConfigPagesImport::CONFIG_PAGES_MODULE . '.config_pages_import_test_fail.default');
    $this->assertEmpty($defaultViewDisplay);
  }

  /**
   * @group config_pages_import
   */
  public function testConfigPagesTypeAlreadyExistsException()
  {
    $testConfigEntity = 'config_pages_import_test_simple';
    // first
    $this->import($testConfigEntity);

    // second
    $this->expectException(EntityStorageException::class);
    $this->import($testConfigEntity);
  }

  /**
   * @group config_pages_import
   */
  public function testMissedSchemaException()
  {
    $testConfigEntity = 'some_weird_config_name';

    $this->expectException(ConfigPagesImportException::class);

    $this->import($testConfigEntity);
  }

  /**
   * Run config pages import
   *
   * @param $configEntityName
   *
   * @return ConfigPagesType
   */
  private function import($configEntityName)
  {
    $import = \Drupal::service('config_pages_import');
    return $import->import($configEntityName);
  }

}