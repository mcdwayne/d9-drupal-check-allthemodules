<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\type_processor;

use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\Core\TypedData\TypedData;
use Drupal\content_synchronizer\Processors\Type\TypeProcessorBase;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation For the type processor .
 *
 * @TypeProcessor(
 *   id = "content_synchronzer_field_item_list_type_processor",
 *   fieldType = "Drupal\Core\Field\FieldItemList"
 * )
 */
class FieldItemListProcessor extends TypeProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getExportedData(TypedData $propertyData) {
    $data = [];
    foreach ($propertyData as $order => $value) {
      $data[] = $value->getValue();
    }

    $this->exportIncludedImages($data);

    return $data;
  }

  /**
   * Add all the included image in the data.
   *
   * @param array $data
   *   The item data.
   */
  protected function exportIncludedImages(array &$data) {
    foreach ($data as &$item) {
      foreach ($item as $key => $value) {
        preg_match_all('@src="([^"]+)"@', $value, $match);

        $src = array_pop($match);
        if (!empty($src)) {
          foreach ($src as $image) {
            $this->exportImage($image, $item, $key, $value);
          }
        }
      }

    }

  }

  /**
   * Add the included image in the export.
   *
   * @param string $image
   *   The image src.
   * @param array $data
   *   The item data.
   * @param string $key
   *   The item key.
   * @param mixed $value
   *   The item value.
   */
  protected function exportImage($image, array &$data, $key, $value) {
    $fileNameData = explode('/files/', urldecode($image));
    $fileName = end($fileNameData);

    /** @var \Drupal\file\Entity\File $file */
    $file = \Drupal::entityQuery('file')
      ->condition('uri', '%://' . $fileName, 'LIKE')
      ->execute();

    if ($file = File::load(reset($file))) {
      $plugin = $this->pluginManager->getInstanceByEntityType($file->getEntityTypeId());
      if ($fileGid = $plugin->export($file)) {
        $data[$key] = str_replace($file->uuid(), $fileGid, $data[$key]);
        $data['included_images'] = $fileGid;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initImportedEntity(EntityInterface $entityToImport, $propertyId, array $data) {
    if (array_key_exists($propertyId, $data)) {
      foreach ($data[$propertyId] as $item) {
        if (array_key_exists('included_images', $item)) {
          $this->importIncludedImage($item['included_images'], $item);
        }
      }

      $entityToImport->set($propertyId, $data[$propertyId]);
    }
  }

  /**
   * Import the included files.
   *
   * @param string $fileGID
   *   The file export gid.
   * @param array $item
   *   The item.
   */
  protected function importIncludedImage($fileGID, array &$item) {
    if ($entityData = ImportProcessor::getCurrentImportProcessor()
      ->getImport()
      ->getEntityDataFromGid($fileGID)
    ) {
      // Import the file.
      $plugin = $this->pluginManager->getInstanceByEntityType($this->referenceManager->getEntityTypeFromGid($fileGID));
      $file = $plugin->import($entityData);

      // Set the file uuid in the data value.
      unset($item['included_images']);
      foreach ($item as $key => $value) {
        $item[$key] = str_replace($fileGID, $file->uuid(), $value);
      }
    }
  }

}
