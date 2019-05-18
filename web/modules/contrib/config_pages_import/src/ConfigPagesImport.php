<?php

namespace Drupal\config_pages_import;


use Drupal\config_pages\ConfigPagesInterface;
use Drupal\config_pages\ConfigPagesTypeInterface;
use Drupal\config_pages\Entity\ConfigPages;
use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\config_pages_import\Exception\ConfigPagesImportException;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ConfigPagesImport
 *
 * @package Drupal\config_pages_import
 */
class ConfigPagesImport implements ConfigPagesImportInterface
{

  /**
   * Name of Config Pages entity
   */
  const CONFIG_PAGES_MODULE = 'config_pages';

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var MessengerInterface|null
   */
  protected $messenger;

  /**
   * ConfigPagesImport constructor.
   *
   * @param MessengerInterface|null $messenger
   * @param LoggerInterface|null $logger
   */
  public function __construct(MessengerInterface $messenger = null, LoggerInterface $logger = null)
  {
    if (empty($logger)) {
      $this->logger = \Drupal::logger('config_pages_import');
    } else {
      $this->logger = $logger;
    }

    if (empty($messenger)) {
      $this->messenger = \Drupal::messenger();
    } else {
      $this->messenger = $messenger;
    }
  }

  /**
   * Import config entities from module
   *
   * @param string $moduleName
   *
   * @return array
   *
   * @throws \Exception
   */
  public function importFromModule(string $moduleName) {

    try {

      $configEntities = $this->getConfigEntitiesFromInfo($moduleName);

      // save results to delete them if something is wrong
      $results = [];
      foreach ($configEntities as $configEntityName) {
        $results[] = $this->import($configEntityName);
      }

      $message = 'Configs from module "' . $moduleName . '" was successfully imported';
      $this->messenger->addMessage($message, MessengerInterface::TYPE_STATUS);
      $this->logger->info($message);

      return $results;

    } catch (\Exception $e) {

      // delete garbage if something went wrong
      if (!empty($results)) {
        foreach ($results as $configEntityType) {
          $configEntityType->delete();
        }
      }

      $message = 'Config import for module "'. $moduleName .'" was failed';
      $this->messenger->addMessage($message, MessengerInterface::TYPE_ERROR);
      $this->logger->error($message);

      // throw the exception further
      throw $e;
    }

  }

  /**
   * Import config entity
   *
   * @param string $configEntityName
   *
   * @return ConfigPagesTypeInterface
   *
   * @throws \Exception
   */
  public function import(string $configEntityName)
  {
    try {
      // Get config entity schema from module info
      $schema = $this->getSchema($configEntityName);

      // Create config page type (a bundle)
      $confogPagesType = $this->createConfigPagesType($schema);

      $values = [];

      // Create a field
      foreach ($schema['mapping'] as $fieldName => $mapping) {

        if (!$this->fieldTypeIsAvailable($mapping['type'])) {
          throw new ConfigPagesImportException('Field type "' . $mapping['type'] . '" is not supported by your system.');
        }

        $fieldStorage = $this->createFieldStorage($fieldName, $mapping);

        $this->createField($fieldName, $mapping, $confogPagesType, $fieldStorage);

        // Assign widget settings for the 'default' form mode.
        $this->createFormDisplay($fieldName, $mapping, $confogPagesType, $schema['third_party_settings']['form_display'] ?? []);

        // Assign display settings for the 'default' and 'teaser' view modes.
        $this->createViewDisplay($fieldName, $mapping, $confogPagesType);

        // If field has a value, save it
        if (isset($mapping['value'])) {
          $values[$fieldName] = $mapping['value'];
        }
      }

      // Create Config Page (values) if we have any values in the schema.
      if (count($values) != 0) {
        $this->createConfigPage($configEntityName, $confogPagesType, $schema, $values);
      }

      $message = 'Config "' . $configEntityName . '" was successfully imported';
      $this->messenger->addMessage($message, MessengerInterface::TYPE_STATUS);
      $this->logger->info($message);

      return $confogPagesType;

    } catch (\Exception $e) {

      // delete garbage if something went wrong
      if (!empty($confogPagesType)) {
        $confogPagesType->delete();
      }
      $this->messenger->addMessage($e->getMessage(), MessengerInterface::TYPE_ERROR);
      $this->logger->error($e->getMessage());

      // throw the exception further
      throw $e;
    }
  }

  /**
   * Gets names of config entities from module info
   *
   * @param string $moduleName
   * @return array
   *
   * @throws ConfigPagesImportException
   */
  private function getConfigEntitiesFromInfo(string $moduleName)
  {
    $module = system_get_info('module', $moduleName);
    if (empty($module)) {
      throw new ConfigPagesImportException('Module [' . $moduleName . '] is not found');
    }

    $configEntities = $module[self::CONFIG_PAGES_MODULE];

    if (!empty($configEntities)) {
      if (!is_array($configEntities)) {
        $configEntities = [$configEntities];
      }
      return $configEntities;
    }

    return [];
  }

  /**
   * Gets schema from config entity
   *
   * @param string $configEntityName
   * @return mixed
   *
   * @throws ConfigPagesImportException
   */
  private function getSchema(string $configEntityName)
  {
    $schema = \Drupal::service('config.typed')->getDefinition($configEntityName);
    if ($schema['type'] == 'undefined') {
      throw new ConfigPagesImportException('Config entity [' . $configEntityName . '] is not found');
    }
    if (empty($schema['mapping'])) {
      throw new ConfigPagesImportException('Config entity [' . $configEntityName . '] is not a mapping. Only mappings are supported.');
    }

    return $schema;
  }

  /**
   * Create config page type (a bundle)
   *
   * @param array $schema
   *
   * @return ConfigPagesTypeInterface
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createConfigPagesType(array $schema): ConfigPagesTypeInterface
  {

    $serializer = \Drupal::service('serializer');

    if (empty($schema['context'])) {
      $schema['context'] = ['show_warning' => TRUE, 'group' => ['language' => TRUE]];
    }
    if (!isset($schema['context']['show_warning'])) {
      $schema['context']['show_warning'] = TRUE;
    }
    if (!isset($schema['context']['group']['language'])) {
      $schema['context']['group']['language'] = FALSE;
    }

    if (!isset($schema['menu'])) {
      $schema['menu'] = [
        'path' => '',
        'weight' => 0,
        'description' => '',
      ];
    }

    $configPagesType = ConfigPagesType::create([
      'id' => $schema['type'],
      'label' => $schema['label'],
      'context' => $serializer->normalize($schema['context']),
      'menu' => $serializer->normalize($schema['menu']),
    ]);

    $configPagesType->save();

    return $configPagesType;
  }

  /**
   * Create Config Page (config values)
   *
   * @param string $configEntityName
   * @param ConfigPagesTypeInterface $confogPagesType
   * @param array $schema
   * @param array $values
   *
   * @return ConfigPagesInterface
   */
  private function createConfigPage(string $configEntityName, ConfigPagesTypeInterface $confogPagesType, array $schema, array $values): ConfigPagesInterface
  {
    $configPage = ConfigPages::create([
      'id' => $configEntityName,
      'type' => $confogPagesType->id(),
      'label' => $schema['label'],
      'context' => $confogPagesType->getContextData(),
      'uuid' => \Drupal::service('uuid')->generate(),
    ]);

    foreach ($values as $key => $value) {
      $configPage->set($key, $value);
    }

    $configPage->save();

    return $configPage;
  }

  /**
   * Load or create field storage
   *
   * @param string $fieldName
   * @param array $mapping
   *
   * @return FieldStorageConfigInterface
   *
   * @throws ConfigPagesImportException
   */
  private function createFieldStorage(string $fieldName, array $mapping): FieldStorageConfigInterface
  {
    $fieldStorage = FieldStorageConfig::loadByName(self::CONFIG_PAGES_MODULE, $fieldName);
    if (empty($fieldStorage)) {
      $fieldStorage = FieldStorageConfig::create([
        'entity_type' => self::CONFIG_PAGES_MODULE,
        'field_name' => $fieldName,
        'type' => $mapping['type'],
      ]);
    }

    if (isset($mapping['cardinality']) && empty($mapping['cardinality'])) {
      throw new ConfigPagesImportException('Field (' . $fieldName . ') cardinality can not be equal 0.');
    }
    $fieldStorage->setCardinality($mapping['cardinality'] ?? 1);
    if (isset($mapping['max_length']) and $mapping['max_length']) {
      $fieldStorage->setSetting('max_length', $mapping['max_length']);
    }

    $fieldStorage->save();

    return $fieldStorage;
  }

  /**
   * Create a field for config_page
   *
   * @param string $fieldName
   * @param array $mapping
   * @param ConfigPagesTypeInterface $confogPagesType
   * @param FieldStorageConfigInterface $fieldStorage
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  private function createField(string $fieldName, array $mapping, ConfigPagesTypeInterface $confogPagesType, FieldStorageConfigInterface $fieldStorage)
  {
    $field = FieldConfig::loadByName(self::CONFIG_PAGES_MODULE, $confogPagesType->id(), $fieldName);

    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $fieldStorage,
        'bundle' => $confogPagesType->id(),
      ]);
    }
    $field->setLabel($mapping['label'])
          ->setSettings($mapping['settings'] ?? [])
          ->setRequired($mapping['required'] ?? FALSE)
          ->setDescription($mapping['description'] ?? NULL)
          ->setDefaultValue($mapping['default_value'] ?? NULL)
          ->setDefaultValueCallback($mapping['default_value_callback'] ?? NULL);

    $field->save();

    return $field;
  }

  /**
   * Assign widget settings for the 'default' form mode.
   *
   * @param string $fieldName
   * @param array $mapping
   * @param ConfigPagesTypeInterface $confogPagesType
   * @param array $thirdPartySettings
   *
   * @return EntityFormDisplayInterface
   */
  private function createFormDisplay(string $fieldName, array $mapping, ConfigPagesTypeInterface $confogPagesType, array $thirdPartySettings = []): EntityFormDisplayInterface
  {
    $entityFormDisplay = EntityFormDisplay::load(self::CONFIG_PAGES_MODULE . '.' . $confogPagesType->id() . '.default');
    if (!$entityFormDisplay) {
      $entityFormDisplay = EntityFormDisplay::create([
        'targetEntityType' => self::CONFIG_PAGES_MODULE,
        'bundle' => $confogPagesType->id(),
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    if (!empty($thirdPartySettings)) {
      $entityFormDisplay = $this->setThirdPartySettings($entityFormDisplay, $thirdPartySettings);
    }

    $entityFormDisplay
      ->setComponent($fieldName, $mapping['form_display'] ?? [] )
      ->save();

    return $entityFormDisplay;
  }

  /**
   * Assign display settings for the 'default' view modes.
   *
   * @param string $fieldName
   * @param array $mapping
   * @param ConfigPagesTypeInterface $confogPagesType
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function createViewDisplay(string $fieldName, array $mapping, ConfigPagesTypeInterface $confogPagesType)
  {
    if (!isset($mapping['view_display']['default'])) {
      $mapping['view_display']['default'] = ['label' => 'hidden'];
    }

    foreach ($mapping['view_display'] ?? [] as $mode => $display) {
      $viewDisplay = EntityViewDisplay::load(self::CONFIG_PAGES_MODULE . '.' . $confogPagesType->id() . '.' . $mode);
      if (!$viewDisplay) {
        $viewDisplay = \Drupal::entityTypeManager()
          ->getStorage('entity_view_display')
          ->create([
            'targetEntityType' => self::CONFIG_PAGES_MODULE,
            'bundle' => $confogPagesType->id(),
            'mode' => $mode,
            'status' => TRUE,
          ]);
      }
      $viewDisplay
        ->setComponent($fieldName, $display ?? [])
        ->save();
    }

  }

  /**
   * Check field type availability.
   *
   * @param string $fieldType
   *
   * @return bool
   */
  private function fieldTypeIsAvailable(string $fieldType): bool
  {
    $fieldTypes = array_keys(\Drupal::service('plugin.manager.field.field_type')->getDefinitions());

    return in_array($fieldType, $fieldTypes);
  }

  /**
   * Set third party settings in ConfigPagesType
   *
   * @param ThirdPartySettingsInterface $entity
   * @param array $thirdPartySettings
   *
   * @return ThirdPartySettingsInterface
   */
  private function setThirdPartySettings(ThirdPartySettingsInterface $entity, array $thirdPartySettings): ThirdPartySettingsInterface
  {
    foreach ($thirdPartySettings as $module => $setting) {
      foreach ($setting as $key => $value) {
        $entity->setThirdPartySetting($module, $key, $value);
      }
    }

    return $entity;
  }
}