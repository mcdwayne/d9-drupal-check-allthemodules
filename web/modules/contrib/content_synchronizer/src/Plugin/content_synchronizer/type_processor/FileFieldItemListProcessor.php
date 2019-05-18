<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\type_processor;

use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\content_synchronizer\Processors\Type\TypeProcessorBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\file\Entity\File;

/**
 * Plugin implementation For the type processor .
 *
 * @TypeProcessor(
 *   id = "content_synchronzer_file_field_item_list",
 *   fieldType = "Drupal\file\Plugin\Field\FieldType\FileFieldItemList"
 * )
 */
class FileFieldItemListProcessor extends TypeProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getExportedData(TypedData $propertyData) {

    $dataToExport = [];

    // Get list of data :
    /** @var \Drupal\file\Entity\FileItem $data */
    foreach ($propertyData as $data) {

      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($data->target_id);
      $plugin = $this->pluginManager->getInstanceByEntityType($file->getEntityTypeId());
      if ($fileGid = $plugin->export($file)) {
        $values = $data->toArray();
        unset($values['target_id']);
        $values[ExportEntityWriter::FIELD_GID] = $fileGid;
        $dataToExport[] = $values;
      }
    }

    return $dataToExport;
  }

  /**
   * {@inheritdoc}
   */
  public function initImportedEntity(EntityInterface $entityToImport, $propertyId, array $data) {

    /** @var \Drupal\Core\Entity\EntityReferenceFieldItemList $referenceField */
    $referenceField = $entityToImport->get($propertyId);

    // Empty previous references.
    while ($referenceField->count() > 0) {
      $referenceField->removeItem(0);
    }

    foreach ($data[$propertyId] as $fileItem) {
      $fileGID = $fileItem[ExportEntityWriter::FIELD_GID];
      if ($entityData = ImportProcessor::getCurrentImportProcessor()
        ->getImport()
        ->getEntityDataFromGid($fileGID)
      ) {
        $plugin = $this->pluginManager->getInstanceByEntityType($this->referenceManager->getEntityTypeFromGid($fileGID));
        if ($referencedEntity = $plugin->import($entityData + $fileItem)) {
          $fileItem['target_id'] = $referencedEntity->id();
          unset($fileItem[ExportEntityWriter::FIELD_GID]);
          $referenceField->appendItem($fileItem);
        }
      }
    }

  }

}
