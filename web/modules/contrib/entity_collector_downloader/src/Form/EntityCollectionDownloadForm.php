<?php

namespace Drupal\entity_collector_downloader\Form;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Drupal\entity_collector\EntityCollectionSourceFieldManager;
use Drupal\entity_collector\Service\EntityCollectionManagerInterface;
use Drupal\entity_collector_downloader\Service\EntityCollectionDownloadManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use ZipStream\ZipStream;

/**
 * Class EntityCollectionDownloadForm
 *
 * @package Drupal\entity_collector_downloader\Form
 */
class EntityCollectionDownloadForm extends FormBase {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\entity_collector\Service\EntityCollectionManagerInterface
   */
  protected $entityCollectionManager;

  /**
   * @var \Drupal\entity_collector\EntityCollectionSourceFieldManager
   */
  protected $entityCollectionSourceFieldManager;

  /**
   * @var \Drupal\entity_collector_downloader\Service\EntityCollectionDownloadManagerInterface
   */
  protected $entityCollectionDownloadManager;

  /**
   * @var ContentEntityInterface;
   */
  protected $entity;

  /**
   * EntityCollectionDownloadForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\entity_collector\Service\EntityCollectionManagerInterface $entityCollectionManager
   * @param \Drupal\entity_collector\EntityCollectionSourceFieldManager $entityCollectionSourceFieldManager
   * @param \Drupal\entity_collector_downloader\Service\EntityCollectionDownloadManagerInterface $entityCollectionDownloadManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityCollectionManagerInterface $entityCollectionManager, EntityCollectionSourceFieldManager $entityCollectionSourceFieldManager, EntityCollectionDownloadManagerInterface $entityCollectionDownloadManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityCollectionManager = $entityCollectionManager;
    $this->entityCollectionSourceFieldManager = $entityCollectionSourceFieldManager;
    $this->entityCollectionDownloadManager = $entityCollectionDownloadManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_collection.manager'),
      $container->get('entity_collection.source_field_manager'),
      $container->get('entity_collection_download.manager')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'entity_collection_download_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityCollectionInterface $entity_collection = NULL) {
    $entityCollection = $entity_collection;
    $this->setEntity($entityCollection);

    /** @var \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType */
    $entityCollectionType = $this->entityTypeManager->getStorage('entity_collection_type')
      ->load($entityCollection->bundle());
    $configEntityTypeId = $this->entityTypeManager->getStorage($entityCollectionType->getSource())
      ->getEntityType()
      ->getBundleEntityType();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $configEntities */
    $configEntities = $this->entityTypeManager->getStorage($configEntityTypeId)
      ->loadMultiple();
    $fieldNames = $this->entityCollectionDownloadManager->getActiveDownloadFieldNames($entityCollectionType);
    $sourceFieldDefinition = $this->entityCollectionSourceFieldManager->getSourceFieldDefinition($entityCollectionType, $entityCollectionType->getSource());
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $sourceEntityReferenceField */
    $collectionEntities = $entityCollection->get($sourceFieldDefinition->getName())
      ->referencedEntities();
    $bundledCollectionEntities = $this->groupEntitiesByBundle($collectionEntities);
    $currentUser = $this->currentUser();

    $form['collection_item_sections'] = [
      '#tree' => TRUE,
    ];

    foreach ($configEntities as $configEntity) {

      if (!isset($bundledCollectionEntities[$configEntity->id()])) {
        continue;
      };

      $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($configEntity->getEntityType()
        ->getBundleOf(), $configEntity->id());

      foreach ($fieldNames as $fieldName) {

        if (!isset($fieldDefinitions[$fieldName])) {
          continue;
        }

        $downloadOptions = $this->entityCollectionDownloadManager->getConfigEntityFieldDownloadOptions($entityCollectionType, $configEntity, $currentUser, $fieldName);

        if (empty($downloadOptions)) {
          continue;
        }

        $form['collection_item_sections'][$configEntity->id()][$fieldName] = $this->buildDownloadSectionElements($entityCollectionType, $configEntity, $fieldDefinitions[$fieldName], $bundledCollectionEntities[$configEntity->id()], $downloadOptions);
      }
    }

    $form['entity_collection_id'] = [
      '#type' => 'value',
      '#value' => $entityCollection->id(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Download'),
    ];

    return $form;
  }

  /**
   * Set the entity collection for this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  private function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * @param ContentEntityInterface[] $collectionEntities
   */
  private function groupEntitiesByBundle(array $collectionEntities) {
    $bundledCollectionEntities = [];

    foreach ($collectionEntities as $entity) {
      $bundledCollectionEntities[$entity->bundle()][$entity->id()] = $entity;
    }

    return $bundledCollectionEntities;
  }

  /**
   * Build the element for a section of downloads.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $configEntity
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   * @param \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptions
   *
   * @return array
   */
  private function buildDownloadSectionElements(EntityCollectionTypeInterface $entityCollectionType, ConfigEntityInterface $configEntity, FieldDefinitionInterface $fieldDefinition, $entities, $downloadOptions) {
    return [
      '#type' => 'entity_collection_download_section',
      '#collection_items' => $entities,
      '#view_mode' => $entityCollectionType->getThirdPartySetting('entity_collector_downloader', 'entity_collection_downloader_view_mode'),
      '#field' => $fieldDefinition,
      '#download_options' => $downloadOptions,
      '#title' => $configEntity->label(),
    ];
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $submittedFileDownloadOptions = $this->getSubmittedDownloadOptions($form_state);

    if (!empty($submittedFileDownloadOptions)) {
      return;
    }

    $form_state->setError($form['entity_collection_id'], $this->t('No chosen download options submitted.'));
  }

  /**
   * Get the submitted download option values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  private function getSubmittedDownloadOptions(FormStateInterface $form_state) {
    $values = $form_state->getValue('collection_item_sections');
    $filteredSubmittedValues = $this->arrayFilterRecursive($values);
    return $this->getNestedKeyValues($filteredSubmittedValues);
  }

  /**
   * Filter empty values from the multidimensional array.
   *
   * @param array $input
   *
   * @return array
   */
  private function arrayFilterRecursive(array $input) {
    foreach ($input as &$value) {
      if (is_array($value)) {
        $value = $this->arrayFilterRecursive($value);
      }
    }

    return array_filter($input);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entityCollection = $this->getEntity();

    $submittedFileDownloadOptions = $this->getSubmittedDownloadOptions($form_state);
    $downloadFilePaths = $this->getFileDownloadUri($submittedFileDownloadOptions);

    if(empty($downloadFilePaths)) {
      drupal_set_message('The selected download options are not accessible for download at the moment, please wait a little while or contact an administrator.', 'error');
      $form_state->setRebuild(TRUE);
      return;
    }

    $zipFileName = $entityCollection->label() . '.zip';
    $this->streamZipFile($zipFileName, $downloadFilePaths);
  }

  /**
   * Get the entity collection for this form.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  private function getEntity() {
    return $this->entity;
  }

  /**
   * Get the file download uri from the submitted file download options.
   *
   * @param array $submittedFileDownloadOptions
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function getFileDownloadUri(array $submittedFileDownloadOptions) {
    $downloadFilePaths = [];
    $downloadOptionConfigIds = [];
    foreach ($submittedFileDownloadOptions as $fileDownloadOptionIds) {
      $downloadOptionConfigIds += $fileDownloadOptionIds;
    }

    /** @var \Drupal\file_downloader\Entity\DownloadOptionConfigInterface[] $downloadOptionConfigEntities */
    $downloadOptionConfigEntities = $this->entityTypeManager->getStorage('download_option_config')
      ->loadMultiple($downloadOptionConfigIds);
    $fileIds = array_keys($submittedFileDownloadOptions);
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')
      ->loadMultiple($fileIds);

    foreach ($files as $file) {
      $downloadOptionConfigIds = $submittedFileDownloadOptions[$file->id()];
      foreach ($downloadOptionConfigIds as $downloadOptionConfigId) {
        $downloadOptionConfig = $downloadOptionConfigEntities[$downloadOptionConfigId];
        if (!$downloadOptionConfig->accessDownload($this->currentUser(), $file)) {
          continue;
        }
        $downloadOptionPlugin = $downloadOptionConfig->getPlugin();
        $filePath = $downloadOptionPlugin->getFileUri($file);
        $fileName = pathinfo($filePath, PATHINFO_BASENAME);
        $downloadFilePaths[$filePath] = $downloadOptionConfigId . '/' . $fileName;
      }
    }

    $downloadFilePaths = array_unique($downloadFilePaths);
    return $downloadFilePaths;
  }

  /**
   * Stream a zip file with the download file paths.
   *
   * @param string $zipFileName
   * @param array $downloadFilePaths
   *
   * @throws \ZipStream\Exception\FileNotFoundException
   * @throws \ZipStream\Exception\FileNotReadableException
   */
  private function streamZipFile($zipFileName, $downloadFilePaths) {
    ob_clean();

    $zip = new ZipStream($zipFileName);
    foreach ($downloadFilePaths as $filePath => $fileName) {
      $zip->addFileFromPath($fileName, $filePath);
    }

    $zip->finish();

    ob_end_flush();
    exit();
  }

  /**
   * Get nested values from a multi-dimensional array.
   *
   * @param array $values
   *
   * @return array
   */
  private function getNestedKeyValues(array $values, &$nestedValues = []) {
    foreach($values as $key => $value) {
      if(is_array($value)) {
        $this->getNestedKeyValues($value, $nestedValues);
      }
      else {
        $nestedValues[$key][$value] = $value;
      }
    }
    return $nestedValues;
  }

}
