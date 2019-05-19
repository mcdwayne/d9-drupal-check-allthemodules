<?php

/**
 * @file
 * Contains \Drupal\lingotek\LingotekConfigTranslationService.
 */

namespace Drupal\smartling_config_translation;

use Drupal\config_translation\ConfigEntityMapper;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\config_translation\ConfigNamesMapper;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\lingotek\Entity\LingotekConfigMetadata;
use Drupal\smartling\ApiWrapper\ApiWrapperInterface;

/**
 * Service for managing Smartling configuration translations.
 */
class SmartlingConfigTranslationService {//implements LingotekConfigTranslationServiceInterface {

  /**
   * The language-locale mapper.
   *
   * @var \Drupal\lingotek\LanguageLocaleMapperInterface
   */
  protected $languageLocaleMapper;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  protected $languageManager;

  /**
   * A array of configuration mapper instances.
   *
   * @var \Drupal\config_translation\ConfigMapperInterface[]
   */
  protected $mappers;

  /**
   * Constructs a new LingotekConfigTranslationService object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The configuration mapper manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ConfigMapperManagerInterface $mapper_manager) {
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    $this->configMapperManager = $mapper_manager;
    $this->mappers = $mapper_manager->getMappers();
  }


  /**
   * {@inheritDoc}
   */
  public function getConfigTranslatableProperties($names) {//ConfigNamesMapper $mapper) {
    /** @var TypedConfigManagerInterface $typed_config */
    $typed_config = \Drupal::service('config.typed');

    $properties = [];

    if ($names instanceof ConfigNamesMapper) {
      $names = $names->getConfigNames();
    }
    //foreach ($mapper->getConfigNames() as $name) {
    foreach ($names as $name) {
      $schema = $typed_config->get($name);
      $properties[$name] = $this->getTranslatableProperties($schema, NULL);
    }
    return $properties;
  }

  /**
   * Get the translatable properties for this schema.
   *
   * @param $schema
   *   The schema
   * @param $base_key
   *   The base name for constructing the canonical name.
   * @return array
   *   Canonical names of the translatable properties.
   */
  protected function getTranslatableProperties($schema, $base_key) {
    $properties = [];
    $definition = $schema->getDataDefinition();
    if (isset($definition['form_element_class'])) {
      foreach ($schema as $key => $element) {
        $element_key = isset($base_key) ? "$base_key.$key" : $key;
        $definition = $element->getDataDefinition();

        if ($element instanceof TraversableTypedDataInterface) {
          $properties = array_merge($properties, $this->getTranslatableProperties($element, $element_key));
        }
        else {
          if (isset($definition['form_element_class'])) {
            $properties[] = $element_key;
          }
        }
      }
    }
    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function getSourceData(ConfigEntityInterface $entity) {
    /** @var ConfigEntityMapper $mapper */
    $mapper = $this->configMapperManager->createInstance($entity->getEntityTypeId());
    $mapper->setEntity($entity);
    $properties = $this->getConfigTranslatableProperties($mapper);
    $values = [];
    foreach ($mapper->getConfigNames() as $config_name) {
      foreach ($properties[$config_name] as $property) {
        $keys = explode('.', $property);
        $value = $entity;
        foreach ($keys as $key) {
          if (is_array($value)) {
            $value = $value[$key];
          }
          else {
            $value = $value->get($key);
          }
        }
        $values[$property] = $value;
      }
    }
    return $values;
  }


  /**
   * {@inheritdoc}
   */
  public function getConfigSourceData($names) {//ConfigNamesMapper $mapper) {
    $properties = $this->getConfigTranslatableProperties($names);//$mapper);
    $values = [];
    foreach ($properties as $config_name => $config_properties) {
      $config = \Drupal::configFactory()->getEditable($config_name);
      foreach ($config_properties as $property) {
        $values[$config_name][$property] = $config->get($property);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function uploadConfig($xml, $file_name, $locales = []) {
    //todo: Merge with
    $dir = 'public://smartling/content';
    $success = file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $success = $success && file_save_htaccess($dir);
    if (!$success) {
      return;
    }

    $file_path = file_unmanaged_save_data($xml, $dir . '/' . $file_name, FILE_EXISTS_REPLACE);
    if (!$file_path) {
      return;
    }

    $api = \Drupal::getContainer()->get('smartling.api_wrapper');
    return $api->uploadFile(
      \Drupal::getContainer()->get('file_system')->realpath($file_path),
      $file_name,
      ApiWrapperInterface::TYPE_XML, $locales
    );

  }


   /**
   * {@inheritdoc}
   */
  public function downloadConfig($file_name, $locale) {
    $params = [
      'retrievalType' => 'pseudo',
    ];

    //$locale = $this->convertLocaleDrupalToSmartling($langcode);
    $api = \Drupal::getContainer()->get('smartling.api_wrapper')->getApi();
    return $api->downloadFile($file_name, $locale, $params);
  }

  protected function getConfigId($type, $name) {
    static $names;

    if (empty($names)) {
      $names = \Drupal::configFactory()->listAll('');
    }

    foreach ($names as $nn) {
      if (strpos($nn, $name) !== FALSE && strpos($nn, $type) !== FALSE) {
        return $nn;
      }
    }
    return '';
  }

  public function saveConfig($type, $name, $locale, $data) {
    $id = $this->getConfigId($type, $name);

    //print_r($names);
//    die();

    $config_translation = $this->languageManager->getLanguageConfigOverride($locale, $id);

    foreach ($data as $name => $properties) {
      foreach ($properties as $property => $value) {
        $config_translation->set($property, $value);
      }
      $config_translation->save();
    }
  }
}
