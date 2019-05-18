<?php

namespace Drupal\Tests\acquia_contenthub\Kernel;

use Acquia\ContentHubClient\CDFDocument;
use Acquia\ContentHubClient\Event\GetCDFTypeEvent;
use Acquia\ContentHubClient\EventSubscriber\DefaultCDF;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations;
use Drupal\Tests\acquia_contenthub\Kernel\Stubs\DrupalVersion;

/**
 * Class ImportExportTestBase.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel
 */
class ImportExportTestBase extends EntityKernelTestBase {

  use DrupalVersion;

  const ENTITY_REFERENCE_TYPES = [
    'file',
    'entity_reference',
    'entity_reference_revisions',
  ];

  const ENTITY_REFERENCE_IMAGE_TYPE = 'image';

  protected $fixtures = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'depcalc',
    'acquia_contenthub',
  ];

  /**
   * Returns fixture content.
   *
   * @param int $delta
   *   Fixture delta.
   *
   * @return false|string
   *   Fixture string if file exists.
   *
   * @throws \Exception
   */
  protected function getFixtureString(int $delta) {
    if (!empty($this->fixtures[$delta])) {
      $version_directory = $this->getDrupalVersion();
      $path_to_fixture = sprintf("%s/tests/fixtures/import/$version_directory/%s",
        drupal_get_path('module', 'acquia_contenthub'),
        $this->fixtures[$delta]['cdf']
      );
      return file_get_contents($path_to_fixture);
    }

    throw new \Exception(sprintf("Missing fixture for delta %d in class %s", $delta, __CLASS__));
  }

  /**
   * Returns fixture expectations.
   *
   * @param int $delta
   *   Delta.
   *
   * @return mixed
   *   Expectations array.
   *
   * @throws \Exception
   */
  protected function getFixtureExpectations(int $delta) {
    if (!empty($this->fixtures[$delta])) {
      $version_directory = $this->getDrupalVersion();
      $path_to_fixture = sprintf("%s/tests/fixtures/import/$version_directory/%s",
        drupal_get_path('module', 'acquia_contenthub'),
        $this->fixtures[$delta]['expectations']
      );

      return include $path_to_fixture;
    }

    throw new \Exception(sprintf("Missing expectations for delta %d in class %s", $delta, __CLASS__));
  }

  /**
   * Creates CDF document from fixture.
   *
   * @param int $delta
   *   The delta of the filename to retrieve from the local fixtures property.
   *
   * @return \Acquia\ContentHubClient\CDFDocument
   *   CDF document.
   *
   * @throws \Exception
   */
  protected function createCdfDocumentFromFixture(int $delta): CDFDocument {
    $json = $this->getFixtureString($delta);
    $data = Json::decode($json);
    $document_parts = [];
    foreach ($data['entities'] as $entity) {
      $document_parts[] = $this->populateCdfObject($entity);
    }

    $cdf_document = new CDFDocument(...$document_parts);

    return $cdf_document;
  }

  /**
   * Populates CDF object from array.
   *
   * @param array $data
   *   CDFObject data.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject
   *   Populated CDF object.
   *
   * @throws \Exception
   *
   * @see \Acquia\ContentHubClient\ContentHubClient::getEntities()
   */
  protected function populateCdfObject(array $data) {
    $event = new GetCDFTypeEvent($data);
    $subscriber = new DefaultCDF();
    $subscriber->onGetCDFType($event);
    return $event->getObject();
  }

  /**
   * Import fixture.
   *
   * @param int $delta
   *   Delta.
   *
   * @return mixed
   *   Expectations.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  protected function importFixture(int $delta) {
    $expectations = $this->getFixtureExpectations($delta);
    $document = $this->createCdfDocumentFromFixture($delta);
    $stack = new DependencyStack();
    $this->getSerializer()->unserializeEntities($document, $stack);
    return $expectations;
  }

  /**
   * Executes the set of import/export tests on a configuration entity.
   *
   * @param int $delta
   *   Fixture delta.
   * @param array $validate_data
   *   Data.
   * @param string $export_type
   *   Exported entity type.
   * @param string $export_uuid
   *   Entity UUID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function configEntityImportExport(int $delta, array $validate_data, $export_type, $export_uuid) {
    $expectations = $this->importFixture($delta);

    foreach ($validate_data as $item) {
      list('type' => $type, 'uuid' => $uuid) = $item;
      if (!isset($expectations[$uuid])) {
        throw new \Exception(sprintf('You are missing validation for the entity of type %s of uuid %s.', $type, $uuid));
      }

      /** @var \Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations $expectation */
      $expectation = $expectations[$uuid];

      /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Config\Entity\ConfigEntityType $entity */
      $entity = $this->getEntity($type, $uuid, $expectation);

      /** @var \Drupal\Core\Config\Entity\ConfigEntityType $entity_type */
      $entity_type = $entity->getEntityType();
      $config_name = $entity_type->getConfigPrefix() . '.' . $entity->get($entity_type->getKey('id'));

      // Perform assertions against imported configuration entity.
      $this->assertImportedConfigEntity($expectation, $config_name);
    }

    // Perform assertions against exported configuration entities.
    $expectation = $expectations[$export_uuid] ?? NULL;
    $this->assertExportedConfigEntities($delta, $export_type, $export_uuid, $expectation);
  }

  /**
   * Returns Entity object.
   *
   * @param string $type
   *   Entity type.
   * @param string $uuid
   *   Entity UUID.
   * @param \Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations $expectation
   *   The Expectation object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   *
   * @see CdfExpectations::setEntityLoader()
   */
  protected function getEntity($type, $uuid, CdfExpectations $expectation = NULL): EntityInterface {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = $this->container->get('entity.repository');
    $entity = $repository->loadEntityByUuid($type, $uuid);
    if ($entity) {
      return $entity;
    }

    // Some configuration entities may change UUID value on import, such as
    // "view" configuration entity. So give them a chance to be loaded via
    // a custom entity loader (fallback) provided in the expectation definition.
    if ($expectation && $entity_loader = $expectation->getEntityLoader()) {
      $entity = call_user_func($entity_loader);
      if ($entity) {
        return $entity;
      }
    }

    throw new \Exception(sprintf('Failed to load entity of %s type by uuid=%s.', $type, $uuid));
  }

  /**
   * Executes assertions on an imported configuration entity.
   *
   * @param \Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations $expectation
   *   Expectation object.
   * @param string $config_name
   *   Unique entity configuration name.
   *
   * @throws \Exception
   */
  protected function assertImportedConfigEntity(CdfExpectations $expectation, string $config_name): void {
    if (!$expectation->getLangcodes()) {
      // Perform language-agnostic assertions.
      foreach ($expectation->getFieldNames() as $field_name) {
        $actual_value = \Drupal::config($config_name)->get($field_name);
        $expected_value = $expectation->getFieldValue($field_name);
        $this->assertEquals($expected_value, $actual_value);
      }

      return;
    }

    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = $this->container->get('language_manager');

    // Get the default language.
    $default_language = $language_manager->getCurrentLanguage();

    foreach ($expectation->getLangcodes() as $langcode) {
      $language = $language_manager->getLanguage($langcode);
      $language_manager->setConfigOverrideLanguage($language);

      foreach ($expectation->getFieldNames() as $field_name) {
        if (FALSE !== strpos($field_name, ':')) {
          // Retrieve a second-level configuration value.
          list($field_name_level1, $field_name_level2) = explode(':', $field_name);
          $actual_value = \Drupal::config($config_name)->get($field_name_level1);
          if (!isset($actual_value[$field_name_level2])) {
            throw new \Exception(sprintf("Failed to get actual value for '%s' field defined in the expectation file ('%s' configuration).", $field_name, $config_name));
          }

          $actual_value = $actual_value[$field_name_level2];
        }
        else {
          $actual_value = \Drupal::config($config_name)->get($field_name);
        }

        $expected_value = $expectation->getFieldValue($field_name, $langcode);
        $this->assertEquals($expected_value, $actual_value);
      }
    }

    // Restore the default language.
    $language_manager->setConfigOverrideLanguage($default_language);
  }

  /**
   * Executes assertions on a set of exported configuration entities.
   *
   * @param int $delta
   *   Delta.
   * @param string $type
   *   Exported entity type.
   * @param string $uuid
   *   Exported entity UUID.
   * @param \Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations $expectation
   *   The Expectation object.
   *
   * @throws \Exception
   */
  protected function assertExportedConfigEntities(int $delta, string $type, string $uuid, CdfExpectations $expectation = NULL): void {
    $entity = $this->getEntity($type, $uuid, $expectation);

    $wrapper = new DependentEntityWrapper($entity);
    $stack = new DependencyStack();
    $this->getCalculator()->calculateDependencies($wrapper, $stack);
    $entities = NestedArray::mergeDeep([$wrapper->getUuid() => $wrapper], $stack->getDependenciesByUuid(array_keys($wrapper->getDependencies())));
    $data = $this->getSerializer()
      ->serializeEntities(...array_values($entities));
    $document = new CDFDocument(...$data);

    /** @var \Acquia\ContentHubClient\CDF\CDFObject[] $cdf_objects */
    $cdf_objects = [];
    // Reindex objects for easier assertions.
    foreach ($document->getEntities() as $cdf_object) {
      $cdf_objects[$cdf_object->getUuid()] = $cdf_object;
    }

    $count = 0;
    $fixtures = json_decode($this->getFixtureString($delta), TRUE);
    foreach ($fixtures['entities'] as $fixture) {
      $cdf_object = $cdf_objects[$fixture['uuid']] ?? $cdf_objects[$entity->uuid()];
      $object = Yaml::decode(base64_decode($cdf_object->getMetadata()['data']));
      $fixture = Yaml::decode(base64_decode($fixture['metadata']['data']));

      // Exclude UUID keys.
      $langcode = $this->container->get('language_manager')->getDefaultLanguage()->getId();
      unset($object[$langcode]['uuid']);
      unset($fixture[$langcode]['uuid']);

      $this->assertEquals($fixture, $object);
      $count++;
    }

    $this->assertEquals($count, count($cdf_objects));
  }

  /**
   * Import and export content.
   *
   * @param int $delta
   *   Fixture delta.
   * @param array $validate_data
   *   Data.
   * @param string $export_type
   *   Exported entity type.
   * @param string $export_uuid
   *   Entity UUID.
   * @param bool $compare_exports
   *   Runs extended fixture/export comparison. FALSE for mismatched uuids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function contentEntityImportExport(int $delta, array $validate_data, $export_type, $export_uuid, $compare_exports = TRUE) {
    $expectations = $this->importFixture($delta);
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
    $repository = $this->container->get('entity.repository');
    foreach ($validate_data as $datum) {
      $entity_type = $datum['type'];
      $validate_uuid = $datum['uuid'];
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $repository->loadEntityByUuid($entity_type, $validate_uuid);
      if (!isset($expectations[$validate_uuid])) {
        throw new \Exception(sprintf("You are missing validation for the entity of type %s of uuid %s.", $entity_type, $validate_uuid));
      }
      /** @var \Drupal\Tests\acquia_contenthub\Kernel\Stubs\CdfExpectations $expectation */
      $expectation = $expectations[$validate_uuid];
      foreach ($entity->getTranslationLanguages() as $language) {
        $trans = $entity->getTranslation($language->getId());
        /** @var \Drupal\Core\Field\FieldItemListInterface $field */
        foreach ($trans as $field_name => $field) {
          if ($expectation->isExcludedField($field_name)) {
            continue;
          }

          $actual_value = $this->handleFieldValues($field);
          $expected_value = $expectation->getFieldValue($field_name, $language->getId());
          $message = 'File: ' . $this->fixtures[$delta]['expectations'];
          $message .= "\nEntity: " . $trans->uuid();
          $message .= "\nField name: " . $field_name;
          $message .= "\nExpected:\n" . print_r($expected_value, TRUE) . "\nActual:\n" . print_r($actual_value, TRUE);

          $expected_value = $this->cleanLineEndings($expected_value);
          $actual_value = $this->cleanLineEndings($actual_value);
          $this->assertEquals($expected_value, $actual_value, $message);
        }
      }
    }
    $export_entity = $repository->loadEntityByUuid($export_type, $export_uuid);
    $wrapper = new DependentEntityWrapper($export_entity);
    $stack = new DependencyStack();
    $this->getCalculator()->calculateDependencies($wrapper, $stack);
    $entities = NestedArray::mergeDeep([$wrapper->getUuid() => $wrapper], $stack->getDependenciesByUuid(array_keys($wrapper->getDependencies())));
    $data = $this->getSerializer()->serializeEntities(...array_values($entities));
    $document = new CDFDocument(...$data);
    $fixtures = json_decode($this->getFixtureString($delta), TRUE);
    /** @var \Acquia\ContentHubClient\CDF\CDFObject[] $objects */
    $objects = [];
    // Reindex objects for easier assertions.
    foreach ($document->getEntities() as $object) {
      if ($object->getType() !== 'drupal8_content_entity') {
        continue;
      }
      $objects[$object->getUuid()] = $object;
    }
    if (!$compare_exports) {
      return;
    }
    // Exclusively check content entities because configuration will need
    // separate test coverage.
    $count = 0;
    foreach ($fixtures['entities'] as $fixture) {
      if ($fixture['type'] !== 'drupal8_content_entity') {
        continue;
      }
      $count++;
      $object = json_decode(base64_decode($objects[$fixture['uuid']]->getMetadata()['data']), TRUE);
      $fixture = json_decode(base64_decode($fixture['metadata']['data']), TRUE);
      list($fixture, $object) = $this->normalizeFixtureAndObject($fixture, $object);
      $this->assertEquals($fixture, $object);
    }
    $this->assertEquals($count, count($objects));
  }

  /**
   * Get the CDF serializer.
   *
   * @return \Drupal\acquia_contenthub\EntityCdfSerializer
   *   CDF serializer.
   *
   * @throws \Exception
   */
  protected function getSerializer() {
    return \Drupal::service('entity.cdf.serializer');
  }

  /**
   * Get the dependency calculator.
   *
   * @return \Drupal\depcalc\DependencyCalculator
   *   Dependency calculator.
   *
   * @throws \Exception
   */
  protected function getCalculator() {
    return \Drupal::service('entity.dependency.calculator');
  }

  /**
   * Handle custom field types to more accurately match expectations.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field being handled.
   *
   * @return array|mixed
   *   Field value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function handleFieldValues(FieldItemListInterface $field) {
    $values = $field->getValue();
    if (in_array($field->getFieldDefinition()->getType(), self::ENTITY_REFERENCE_TYPES) && $values) {
      $values = [];
      foreach ($field as $item_delta => $item) {
        if ($item->getValue()['target_id']) {
          $values[$item_delta]['target_id'] = $item->entity->uuid();
        }
      }
    }
    if ($field->getFieldDefinition()->getType() === self::ENTITY_REFERENCE_IMAGE_TYPE && $values) {
      $values = [];
      foreach ($field as $item_delta => $item) {
        if ($item->getValue()['target_id']) {
          $values[$item_delta] = $item->getValue();
          $values[$item_delta]['target_id'] = $item->entity->uuid();
        }
      }
    }
    if ($field->getFieldDefinition()->getType() === 'link') {
      foreach ($field as $item_delta => $item) {
        list($uri_type, $uri) = explode(':', $item->getValue()['uri']);
        if ($uri_type === 'entity') {
          list($item_entity_type, $item_entity_id) = explode('/', $uri);
          $uri_entity = $this->entityManager->getStorage($item_entity_type)->load($item_entity_id);
          $values[$item_delta]['uri'] = $uri_entity->uuid();
        }
        else {
          $values[$item_delta]['uri'] = $item->getValue()['uri'];
        }
      }
    }
    return $values;
  }

  /**
   * Normalize fixture and expected object.
   *
   * @param array $fixture
   *   Fixture.
   * @param array $object
   *   Object.
   *
   * @return array
   *   Normalized data.
   */
  protected function normalizeFixtureAndObject(array $fixture, array $object): array {
    $list = [
      'content_translation_created',
      'content_translation_changed',
      'content_translation_outdated',
      'content_translation_source',
      'menu_link',
      'revision_created',
      'revision_translation_affected',
      'revision_log',
    ];

    // If the fixture had no value, we should not evaluate the object.
    foreach ($fixture as $key => $value) {
      if (!$value) {
        $list[] = $key;
      }
    }

    foreach ($list as $item) {
      if (isset($fixture[$item])) {
        unset($fixture[$item]);
      }

      if (isset($object[$item])) {
        unset($object[$item]);
      }
    }

    return [$fixture, $object];
  }

  /**
   * Standardize OS line endings for the sake of comparison.
   *
   * @param array|string $value
   *   The value to process.
   *
   * @return array|string|string[]|null
   */
  protected function cleanLineEndings($value) {
    if (is_string($value)) {
      $value = preg_replace('/(\r\n|\n\r|\r)/', "\n", $value);
    }
    if (is_array($value)) {
      array_walk_recursive($value, function (&$item) {
        $item = $this->cleanLineEndings($item);
      });
    }
    return $value;
  }

}
