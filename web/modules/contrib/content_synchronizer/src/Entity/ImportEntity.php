<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\content_synchronizer\Base\JsonWriterTrait;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Service\GlobalReferenceManager;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\Entity\File;
use Drupal\user\UserInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the Import entity.
 *
 * @ingroup content_synchronizer
 *
 * @ContentEntityType(
 *   id = "import_entity",
 *   label = @Translation("Import"),
 *   handlers = {
 *     "view_builder" = "Drupal\content_synchronizer\Entity\ImportEntityViewBuilder",
 *     "list_builder" = "Drupal\content_synchronizer\ImportEntityListBuilder",
 *     "views_data" = "Drupal\content_synchronizer\Entity\ImportEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\content_synchronizer\Form\ImportEntityForm",
 *       "add" = "Drupal\content_synchronizer\Form\ImportEntityForm",
 *       "edit" = "Drupal\content_synchronizer\Form\ImportEntityForm",
 *       "delete" = "Drupal\content_synchronizer\Form\ImportEntityDeleteForm",
 *     },
 *     "access" = "Drupal\content_synchronizer\ImportEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\content_synchronizer\ImportEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "import_entity",
 *   admin_permission = "administer import entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/import_entity/{import_entity}",
 *     "add-form" = "/admin/structure/import_entity/add",
 *     "edit-form" = "/admin/structure/import_entity/{import_entity}/edit",
 *     "delete-form" = "/admin/structure/import_entity/{import_entity}/delete",
 *     "collection" = "/admin/structure/import_entity",
 *   },
 *   field_ui_base_route = "import_entity.settings"
 * )
 */
class ImportEntity extends ContentEntityBase implements ImportEntityInterface {

  use EntityChangedTrait;
  use JsonWriterTrait;

  const STATUS_NOT_STARTED = 0;
  const STATUS_RUNNING = 1;
  const STATUS_DONE = 2;

  const FIELD_ARCHIVE = 'archive';
  const FIELD_PROCESSING_STATUS = 'processing_status';

  const ENTITY_FIELD_IMPORTING_STATUS = 'status';

  protected $entityTypeData = [];

  protected $rootEntities;


  /**
   * The global reference manager service.
   *
   * @var \Drupal\content_synchronizer\Service\GlobalReferenceManager
   */
  protected $globalReferenceManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'import_entity', $bundle = FALSE, array $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->globalReferenceManager = \Drupal::service(GlobalReferenceManager::SERVICE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * Return the Archive file.
   *
   * @return \Drupal\file\Entity\File
   *   THe archive file.
   */
  public function getArchive() {
    return File::load($this->get(self::FIELD_ARCHIVE)->target_id);
  }

  /**
   * Return the processing status of the import.
   *
   * @return int
   *   The status.
   */
  public function getProcessingStatus() {
    return intval($this->get(self::FIELD_PROCESSING_STATUS)->value);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Import entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Import entity.'))
      ->setSettings([
        'max_length'      => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type'   => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Import is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $extension = 'gz';
    $validators = [
      'file_validate_extensions' => [$extension],
      'file_validate_size'       => [file_upload_max_size()],
    ];

    $fields[self::FIELD_ARCHIVE] = BaseFieldDefinition::create('file')
      ->setLabel(t('Archive'))
      ->setDescription(t('The archive'))
      ->setSetting('upload_validators', $validators)
      ->setSetting('file_extensions', $extension)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'file',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type'        => 'file',
        // doesn't work.
        'description' => [
          // doesn't work.
          'theme'       => 'file_upload_help',
          // doesn't work.
          'description' => t('A Gettext Portable Object file.'),
          // doesn't work.
        ],
        'settings'    => [
          'upload_validators' => $validators,
        ],
        'weight'      => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields[self::FIELD_PROCESSING_STATUS] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Processing status'))
      ->setDescription(t('Processing status'))
      ->setDefaultValue(self::STATUS_NOT_STARTED);

    return $fields;
  }

  /**
   * Return the entities to import list.
   *
   * @return array
   *   The list of roots entities.
   */
  public function getRootsEntities() {

    if (is_null($this->rootEntities)) {
      if (!file_exists($this->getArchiveFilesPath())) {
        $this->unzipArchive();
      }

      $this->rootEntities = $this->getDataFromFile($this->getArchiveFilesPath() . '/' . ExportEntityWriter::ROOT_FILE_NAME . ExportEntityWriter::TYPE_EXTENSION);
      foreach ($this->rootEntities as &$entity) {
        $existingEntity = $this->globalReferenceManager->getExistingEntityByGidAndUuid($entity[ExportEntityWriter::FIELD_GID], $entity[ExportEntityWriter::FIELD_UUID]);
        if ($existingEntity) {
          $entity['status'] = 'update';
          $entity['edit_url'] = Url::fromRoute('entity.' . $existingEntity->getEntityTypeId() . '.edit_form', [$existingEntity->getEntityTypeId() => $existingEntity->id()]);
          $entity['view_url'] = $existingEntity->toUrl();
        }
        else {
          $entity['status'] = 'create';
        }
      }
    }

    return $this->rootEntities;
  }

  /**
   * Check if the entity is a root entity.
   *
   * @param string $gid
   *   The gid of the entity to check.
   *
   * @return bool
   *   The root status.
   */
  public function isRootEntity($gid) {
    foreach ($this->getRootsEntities() as $rootEntity) {
      if ($rootEntity[ExportEntityWriter::FIELD_GID] == $gid) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Return the working directory path.
   *
   * @return string
   *   THe archive files path.
   */
  public function getArchiveFilesPath() {
    return ExportEntityWriter::GENERATOR_DIR . 'import/' . $this->id();
  }

  /**
   * Return the data from the entity type data file.
   *
   * @param string $entityType
   *   THe entity type id.
   *
   * @return array
   *   The list of import data for this entity type.
   */
  public function getDataFromEntityTypeFile($entityType) {
    // Unzip.
    if (!file_exists($this->getArchiveFilesPath())) {
      $this->unzipArchive();
    }

    if (!array_key_exists($entityType, $this->entityTypeData)) {
      $this->entityTypeData[$entityType] = $this->getDataFromFile($this->getArchiveFilesPath() . '/' . $entityType . ExportEntityWriter::TYPE_EXTENSION);
    }
    return $this->entityTypeData[$entityType];
  }

  /**
   * Return the data form the gid.
   *
   * @param string $gid
   *   The gid.
   *
   * @return array
   *   The entity data.
   */
  public function getEntityDataFromGid($gid) {
    $entityTypeId = $this->globalReferenceManager->getEntityTypeFromGid($gid);
    return $this->getDataFromEntityTypeFile($entityTypeId)[$gid];
  }

  /**
   * Return true if the gid is currently importing and is not imported yet.
   *
   * @param string $gid
   *   THe gid.
   */
  public function gidIsCurrentlyImporting($gid) {
    return $this->getEntityDataFromGid($gid)[self::ENTITY_FIELD_IMPORTING_STATUS] == self::STATUS_RUNNING;
  }

  /**
   * Return true if the gid has already been imported.
   *
   * @param string $gid
   *   The gid.
   */
  public function gidHasAlreadyBeenImported($gid) {
    return $this->getEntityDataFromGid($gid)[self::ENTITY_FIELD_IMPORTING_STATUS] == self::STATUS_DONE;
  }

  /**
   * Tag the entity has importing.
   *
   * @param string $gid
   *   The gid.
   */
  public function tagHasImporting($gid) {
    $this->setGidStatus($gid, self::STATUS_RUNNING);
  }

  /**
   * Tag the entity has imported.
   *
   * @param string $gid
   *   The gid.
   */
  public function tagHasImported($gid) {
    $this->setGidStatus($gid, self::STATUS_DONE);
  }

  /**
   * Set the status of the entity.
   *
   * @param string $gid
   *   The gid.
   * @param string $status
   *   The status.
   */
  protected function setGidStatus($gid, $status) {
    // Write json file for next call.
    $entityTypeId = $this->globalReferenceManager->getEntityTypeFromGid($gid);

    $allData = $this->getDataFromEntityTypeFile($entityTypeId);
    $this->entityTypeData[$entityTypeId][$gid][self::ENTITY_FIELD_IMPORTING_STATUS] = $allData[$gid][self::ENTITY_FIELD_IMPORTING_STATUS] = $status;
    $this->writeJson($allData, $this->getArchiveFilesPath() . '/' . $entityTypeId . ExportEntityWriter::TYPE_EXTENSION);
  }

  /**
   * Unzip archive file.
   */
  protected function unzipArchive() {
    // Get file and zip file path.
    if ($file = $this->getArchive()) {
      if ($zipUrl = $file->getFileUri()) {
        $realPathUrl = \Drupal::service('file_system')
          ->realpath($zipUrl);

        // Get the destination dir path.
        $dir = $this->getArchiveFilesPath();
        if (!is_dir($dir)) {
          file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
        }

        $archiver = new ArchiveTar($realPathUrl, 'gz');
        $files = [];
        foreach ($archiver->listContent() as $file) {
          $files[] = $file['filename'];
        }

        $archiver->extractList($files, $dir);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->removeArchive();
    parent::preSave($storage);
  }

  /**
   * Remove unzipped archive.
   */
  public function removeArchive() {
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = \Drupal::service('file_system');
    $fileSystem->deleteRecursive($this->getArchiveFilesPath());
  }

}
