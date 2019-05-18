<?php

namespace Drupal\content_synchronizer\Processors\Entity;

use Drupal\content_synchronizer\Events\ImportEvent;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Processors\ExportProcessor;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\content_synchronizer\Service\EntityPublisher;
use Drupal\content_synchronizer\Service\GlobalReferenceManager;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\content_synchronizer\Processors\Type\TypeProcessorPluginManager;

/**
 * The entity processor base.
 */
class EntityProcessorBase extends PluginBase implements EntityProcessorInterface {

  const KEY_TRANSLATIONS = 'translations';

  const EXPORT_HOOK = 'content_synchronizer_export_data';
  const IMPORT_HOOK = 'content_synchronizer_import_entity';

  protected $propertyIdsNotToExport = [
    'status',
    'revision',
    'revision_timestamp',
    'revision_uid',
    'revision_log',
    'revision_translation_affected',
    'created',
    // 'uuid',.
    'id',
  ];

  /**
   * The global reference manager service.
   *
   * @var \Drupal\content_synchronizer\Service\GlobalReferenceManager
   */
  protected $globalReferenceManager;

  /**
   * The type processor manager service.
   *
   * @var \Drupal\content_synchronizer\Processors\Type\TypeProcessorPluginManager
   */
  protected $typeProcessorManager;

  /**
   * The entity processor manager service.
   *
   * @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager
   */
  protected $entityProcessorManager;

  /**
   * The entity publisher service.
   *
   * @var \Drupal\content_synchronizer\Service\EntityPublisher
   */
  protected $entityPublisher;

  /**
   * The current entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Export the entity and return the gid if exists, else  false.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entityToExport
   *   The entity to export.
   *
   * @return bool|string
   *   The gid if exported, False either
   */
  final public function export(EntityInterface $entityToExport) {
    // If entity is exportable (content entity)
    if ($entityToExport->getEntityType() instanceof ContentEntityType) {
      // Get the entity gid.
      $gid = $this->getEntityGlobalReference($entityToExport);

      if (isset($entityToExport->contentSynchronizerIsExporting)) {
        return $gid;
      }
      else {
        $dataToExport = [];
        foreach ($this->getEntityTranslations($entityToExport) as $languageId => $translation) {
          // Tag the current entity has exporting in order to avoid circular dependencies.
          $translation->contentSynchronizerIsExporting = TRUE;
          $dataToExport[self::KEY_TRANSLATIONS][$languageId] = $this->getDataToExport($translation);

          // Add changed time.
          if (method_exists($translation, 'getChangedTime')) {
            $dataToExport[self::KEY_TRANSLATIONS][$languageId][ExportEntityWriter::FIELD_CHANGED] = $translation->getChangedTime();
          }

          // Custom alter data.
          \Drupal::moduleHandler()
            ->alter(self::EXPORT_HOOK, $dataToExport[self::KEY_TRANSLATIONS][$languageId], $translation);
        }

        if (!empty($dataToExport)) {
          $entityToExport->contentSynchronizerGid = $dataToExport[ExportEntityWriter::FIELD_GID] = $gid;
          $dataToExport[ExportEntityWriter::FIELD_UUID] = $entityToExport->uuid();
          ExportProcessor::getCurrentExportProcessor()
            ->getWriter()
            ->write($entityToExport, $dataToExport);
          return $gid;
        }
      }
    }

    return FALSE;
  }

  /**
   * Return the data of the default language of the passed data.
   */
  public function getDefaultLanguageData(array $data, $filterOnEntityDefinition = TRUE) {
    if (count($data[self::KEY_TRANSLATIONS]) > 1) {
      foreach ($data[self::KEY_TRANSLATIONS] as $languageId => $translationData) {
        if (array_key_exists('default_langcode', $translationData) && $translationData['default_langcode'][0]['value'] == 1) {
          return $translationData;
        }
      }
    }

    // Get default data :
    $defaultData = reset($data[self::KEY_TRANSLATIONS]);

    if ($filterOnEntityDefinition) {
      // Filter on reference data field.
      $fieldDefinitions =
        \Drupal::entityTypeManager()
          ->getStorage($this->getGlobalReferenceManager()
            ->getEntityTypeFromGid($data[ExportEntityWriter::FIELD_GID]))
          ->getFieldStorageDefinitions();

      return array_intersect_key($defaultData, $fieldDefinitions);
    }
    else {
      return $defaultData;
    }

  }

  /**
   * Return the entity translations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array|\Drupal\Core\Language\LanguageInterface[]
   *   The array of translations.
   */
  protected function getEntityTranslations(EntityInterface $entity) {
    $translations = [];
    foreach ($entity->getTranslationLanguages() as $languageId => $data) {
      $translations[$languageId] = \Drupal::service('entity.repository')
        ->getTranslationFromContext($entity, $languageId);
    }

    return array_filter($translations, function ($entity) {
      return $entity->id();
    });
  }

  /**
   * Return a translation.
   *
   * @param string $languageId
   *   The language id.
   * @param \Drupal\Core\Entity\EntityInterface $existingEntity
   *   The entity to translate.
   * @param array $dataToImport
   *   The data to import.
   */
  protected function createNewTranslation($languageId, EntityInterface $existingEntity, array $dataToImport = []) {
    if ($existingEntity->isTranslatable()) {
      if ($existingEntity->language()->getId() == $languageId) {
        return $existingEntity;
      }
      else {
        $translation = $existingEntity->addTranslation($languageId);
        $translation->uuid = \Drupal::service('uuid')->generate();
        return $translation;
      }
    }
    return NULL;
  }

  /**
   * Create or update entity with data :.
   *
   * @param array $dataToImport
   *   The data to import.
   */
  final public function import(array $dataToImport) {
    $gid = $dataToImport[ExportEntityWriter::FIELD_GID];
    $uuid = $dataToImport[ExportEntityWriter::FIELD_UUID];

    // If the entity has already been imported then we don't have to do it again.
    $import = ImportProcessor::getCurrentImportProcessor()->getImport();
    if ($import->gidHasAlreadyBeenImported($gid)) {
      return $this->getGlobalReferenceManager()->getEntityByGid($gid);
    }

    // Tag as importing.
    ImportProcessor::getCurrentImportProcessor()
      ->getImport()
      ->tagHasImporting($gid);

    // Get the previous entity by gid.
    if ($existingEntity = $this->getGlobalReferenceManager()
      ->getExistingEntityByGidAndUuid($gid, $uuid)
    ) {
      if ($existingEntity) {
        $backup = clone($existingEntity);
      }
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if ($entity = $this->getEntityToImport($dataToImport, $existingEntity)) {
        $this->setChangedTime($entity, $dataToImport);
        $this->getEntityPublisher()
          ->saveEntity($entity, $gid, $backup, $dataToImport);
      }
    }
    else {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if ($entity = $this->getEntityToImport($dataToImport, NULL)) {

        $this->checkBundle($entity, TRUE);

        $this->setChangedTime($entity, $dataToImport);
        $this->getEntityPublisher()
          ->saveEntity($entity, $gid, NULL, $dataToImport);

        $this->getGlobalReferenceManager()
          ->createGlobalEntityByImportingEntityAndGid($entity, $gid);
      }
    }

    if ($entity) {
      // Tag as imported.
      ImportProcessor::getCurrentImportProcessor()
        ->getImport()
        ->tagHasImported($gid);

      $this->onEntityImported($gid, $entity);
    }

    return $entity;
  }

  /**
   * Check if entity's bundle exist, create-it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param bool $force_create
   *   Force creation.
   */
  private function checkBundle(EntityInterface $entity, $force_create = FALSE) {
    $bundle_type = $entity->getEntityType()->getBundleEntityType();
    if ($bundle_type == '') {
      return;
    }
    $bundle_name = $entity->bundle();
    $bundle = \Drupal::entityTypeManager()
      ->getStorage($bundle_type)
      ->load($bundle_name);

    if ($bundle == NULL && $force_create === TRUE) {
      $this->createBundle($bundle_type, $bundle_name);
    }
  }

  /**
   * Create missing Bundle.
   *
   * @param string $bundle_type
   *   The bundle.
   * @param string $bundle_name
   *   The bundle name.
   */
  private function createBundle($bundle_type, $bundle_name) {
    $storage = \Drupal::entityTypeManager()->getStorage($bundle_type);
    $data = [];

    switch ($bundle_type) {
      case "taxonomy_vocabulary":
        $data = [
          'vid'    => $bundle_name,
          'name'   => $bundle_name,
          'weight' => 0,
        ];
        break;

      case "media_type":
        $data = [
          'id'     => $bundle_name,
          'label'  => $bundle_name,
          'status' => 1,
        ];
        break;

      case "paragraphs_type":
        $data = [
          'id'    => $bundle_name,
          'label' => $bundle_name,
        ];
        break;

      case "node_type":
        $data = [
          'type' => $bundle_name,
          'name' => $bundle_name,
        ];
        break;

    }

    $bundle = $storage->create($data);
    $bundle->save();
  }

  /**
   * Update the changed time form the data array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param array $dataToImport
   *   THe data to import.
   */
  protected function setChangedTime(EntityInterface $entity, array $dataToImport) {
    $dataToImport = array_key_exists('translations', $dataToImport) ? $dataToImport['translations'][$entity->language()
      ->getId()] : $dataToImport;
    if (array_key_exists(ExportEntityWriter::FIELD_CHANGED, $dataToImport)) {
      if (method_exists($entity, 'setChangedTime')) {
        $entity->setChangedTime($dataToImport[ExportEntityWriter::FIELD_CHANGED]);
      }
    }
  }

  /**
   * Callback when the entity has been imported.
   */
  protected function onEntityImported($gid, EntityInterface $entity) {
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher */
    $dispatcher = \Drupal::service('event_dispatcher');

    $event = new ImportEvent();
    $event->setEntity($entity);
    $event->setGid($gid);

    $dispatcher->dispatch(ImportEvent::ON_ENTITY_IMPORTER, $event);
  }

  /**
   * Return the data to export.
   *
   * Get the array of data to export in array format :
   * [
   *    "property_1"=>[ "value1", "value2"]
   *    "property_2"=>[ "value1"]
   * ].
   *
   * @param \Drupal\Core\Entity\EntityInterface $entityToExport
   *   The entity to export.
   *
   * @return array
   *   The data to export.
   */
  public function getDataToExport(EntityInterface $entityToExport) {
    $dataToExport = [];

    // Get entity type keys :
    $contentEntityTypeKeys = $entityToExport->getEntityType()->getKeys();

    // Init properties not to export.
    $propertyIdsNotToExport = $this->getPropertiesIdsNotToExportList();
    $propertyIdsNotToExport += array_intersect_key($contentEntityTypeKeys, array_flip($propertyIdsNotToExport));

    // Init keys like bundles.
    /** @var \Drupal\Core\Entity\ContentEntityType $contentEntityType */
    foreach ($contentEntityTypeKeys as $key => $name) {
      if (!in_array($name, $propertyIdsNotToExport)) {
        if (method_exists($entityToExport, $key)) {
          $dataToExport[$name] = $entityToExport->$key();
        }
      }
    }

    foreach ($entityToExport->getTypedData()
      ->getProperties() as $propertyId => $propertyData) {
      // Check properties to export :
      if (!in_array($propertyId, $propertyIdsNotToExport)) {
        /** @var \Drupal\content_synchronizer\Processors\Type\TypeProcessorBase $plugin */
        if ($plugin = $this->getTypeProcessorManager()
          ->getInstanceByFieldType(get_class($propertyData))
        ) {
          if ($fieldDataToExport = $plugin->getExportedData($entityToExport->get($propertyId))) {
            $dataToExport[$propertyId] = $fieldDataToExport;
          }
        }
      }
    }

    return $dataToExport;
  }

  /**
   * Return the entity to import.
   *
   * @param array $data
   *   The data to import.
   * @param \Drupal\Core\Entity\EntityInterface $entityToImport
   *   The existing entity to update.
   */
  public function getEntityToImport(array $data, EntityInterface $entityToImport = NULL) {
    if ($entityToImport) {
      $backup = clone($entityToImport);
    }

    // Create Entity.
    if (is_null($entityToImport)) {
      try {
        $typeId = $this->getGlobalReferenceManager()
          ->getEntityTypeFromGid($data[ExportEntityWriter::FIELD_GID]);
        $defaultData = $this->getDefaultLanguageData($data);

        // Get type manager.
        /** @var \Drupal\Core\Entity\ContentEntityType $typeManager */
        $typeManager = \Drupal::entityTypeManager()->getDefinition($typeId);
        $bundleKey = $typeManager->getKey('bundle');

        /** @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $baseDefinitions = $entityFieldManager->getFieldDefinitions($typeId, $data[$bundleKey]);
        $createData = array_intersect_key($defaultData, $baseDefinitions);

        $entityToImport = \Drupal::entityTypeManager()
          ->getStorage($typeId)
          ->create($createData);
      }
      catch (\Exception $e) {
        \Drupal::messenger()
          ->addError('Import Process : ' . $e->getMessage() . ' in "' . __METHOD__ . '()"');
        return NULL;
      }
    }

    // Properties not to import.
    $propertyIdsNotToImport = $this->getPropertiesIdsNotToExportList();

    // Get the existing translations.
    $alreadyExistingEntityTranslations = $this->getEntityTranslations($entityToImport);

    // Update data for each translation.
    foreach ($data[self::KEY_TRANSLATIONS] as $languageId => $translationData) {
      if (!array_key_exists($languageId, $alreadyExistingEntityTranslations)) {
        if ($translation = $this->createNewTranslation($languageId, $entityToImport, $translationData)) {
          $alreadyExistingEntityTranslations[$languageId] = $translation;
        }
        else {
          continue;
        }
      }

      $entityToUpdate = $alreadyExistingEntityTranslations[$languageId];

      // Parse each property of the entity.
      foreach ($entityToUpdate->getTypedData()
        ->getProperties() as $propertyId => $propertyData) {
        // Check properties to import :
        if (!in_array($propertyId, $propertyIdsNotToImport)) {

          /** @var \Drupal\content_synchronizer\Processors\Type\TypeProcessorBase $plugin */
          if ($plugin = $this->getTypeProcessorManager()
            ->getInstanceByFieldType(get_class($propertyData))
          ) {
            $plugin->initImportedEntity($entityToUpdate, $propertyId, $translationData);
          }
        }
      }

      // Save translation.
      if ($entityToImport->language()->getId() != $entityToUpdate->language()
        ->getId()
      ) {
        $this->setChangedTime($entityToImport, $translationData);
        $this->getEntityPublisher()
          ->saveEntity($entityToUpdate, NULL, $backup, $translationData);
      }
    }

    return $entityToImport;
  }

  /**
   * Get the array of the property of the entity not to export.
   *
   * @return array
   *   The property not to export.
   */
  public function getPropertiesIdsNotToExportList() {
    return $this->propertyIdsNotToExport;
  }

  /**
   * Get the global reference entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entityToExport
   *   The entity to export.
   *
   * @return string
   *   The gid.
   */
  protected function getEntityGlobalReference(EntityInterface $entityToExport) {
    $gid = $this->getGlobalReferenceManager()
      ->getEntityGlobalId($entityToExport);
    if (!$gid) {
      $gid = $this->getGlobalReferenceManager()
        ->createEntityGlobalId($entityToExport);
    }
    return $gid;
  }

  /**
   * Get the contentSyncManager.
   *
   * @return \Drupal\content_synchronizer\Service\GlobalReferenceManager
   *   The global reference manager service.
   */
  final protected function getGlobalReferenceManager() {
    if (!isset($this->globalReferenceManager)) {
      $this->globalReferenceManager = \Drupal::service(GlobalReferenceManager::SERVICE_NAME);
    }
    return $this->globalReferenceManager;
  }

  /**
   * Get the TypeProcessor plugin manager.
   *
   * @return \Drupal\content_synchronizer\Processors\Type\TypeProcessorPluginManager
   *   The type processor manager service.
   */
  protected function getTypeProcessorManager() {
    if (!$this->typeProcessorManager) {
      $this->typeProcessorManager = \Drupal::service(TypeProcessorPluginManager::SERVICE_NAME);
    }
    return $this->typeProcessorManager;
  }

  /**
   * Get the EntityProcessor plugin manager.
   *
   * @return \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager
   *   The entity processor manager service.
   */
  protected function getEntityProcessorManager() {
    if (!$this->entityProcessorManager) {
      $this->entityProcessorManager = \Drupal::service(EntityProcessorPluginManager::SERVICE_NAME);
    }
    return $this->entityProcessorManager;
  }

  /**
   * Return the current entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Set the current entity type.
   */
  public function setEntityType($entityType) {
    $this->entityType = $entityType;
  }

  /**
   * Return the entity saver service.
   *
   * @return \Drupal\content_synchronizer\Service\EntityPublisher
   *   The Entity publisher service.
   */
  public function getEntityPublisher() {
    if (is_null($this->entityPublisher)) {
      $this->entityPublisher = \Drupal::service(EntityPublisher::SERVICE_NAME);
    }
    return $this->entityPublisher;
  }

}
