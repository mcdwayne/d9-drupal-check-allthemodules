<?php

namespace Drupal\thumbor_effects_crop;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\crop\CropInterface;
use Drupal\crop\Entity\Crop;
use Drupal\file\FileInterface;

/**
 * Provides Thumbor Effects cropping logic.
 */
class ThumborCropManager implements ThumborCropManagerInterface {

  public const CROP_TYPE_ID = 'thumbor_effects_crop';
  public const ORIENTATION_LANDSCAPE = 'landscape';
  public const ORIENTATION_PORTRAIT = 'portrait';

  /**
   * Crop entity storage.
   *
   * @var \Drupal\crop\CropStorageInterface
   */
  protected $cropStorage;

  /**
   * Constructs a ThumborCropManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->cropStorage = $entity_type_manager->getStorage('crop');
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityInsert(EntityInterface $entity): void {
    $files_and_settings = $this->getFilesWithCropSettings($entity);

    $files_with_crop = array_filter($files_and_settings, function ($file_and_settings) {
      return !empty($file_and_settings['crop_settings']);
    });

    foreach ($files_with_crop as ['crop_settings' => $crop_settings, 'file' => $file]) {
      $crop = $this->createCropForFile($file);
      $this->saveCrop($crop_settings, $crop);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onEntityUpdate(EntityInterface $entity): void {
    foreach ($this->getFilesWithCropSettings($entity) as ['crop_settings' => $crop_settings, 'file' => $file]) {
      $crop = Crop::findCrop($file->getFileUri(), self::CROP_TYPE_ID);

      if ($crop !== NULL && empty($crop_settings['aspect_ratio'])) {
        $this->deleteCrop($crop);
        return;
      }

      if ($crop === NULL) {
        $crop = $this->createCropForFile($file);
      }

      $this->saveCrop($crop_settings, $crop);
    }
  }

  /**
   * Get all files with corresponding crop settings from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity to get files with corresponding crop settings from.
   *
   * @return array
   *   Array of files with corresponding crop settings.
   */
  private function getFilesWithCropSettings(EntityInterface $entity): array {
    if (!$entity instanceof FieldableEntityInterface) {
      return [];
    }

    $result = [];
    foreach ($entity->getFieldDefinitions() as $key => $field) {
      if ($field->getType() !== 'image' || !$entity->hasField($field->getName())) {
        continue;
      }

      foreach ($entity->{$field->getName()} as $item) {
        if (!($item instanceof EntityReferenceItem)) {
          continue;
        }

        $values = $item->getValue();

        if (!isset($values['thumbor_effects_crop'])) {
          continue;
        }

        $result[$item->entity->id()] = [
          'file' => $item->entity,
          'crop_settings' => $values['thumbor_effects_crop'],
        ];
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public static function hasCropForFile(FileInterface $file): bool {
    return Crop::cropExists($file->getFileUri(), self::CROP_TYPE_ID);
  }

  /**
   * {@inheritdoc}
   */
  public static function getCropForFile(FileInterface $file): CropInterface {
    return Crop::findCrop($file->getFileUri(), self::CROP_TYPE_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function createCropForFile(FileInterface $file): CropInterface {
    $values = [
      'type' => static::CROP_TYPE_ID,
      'entity_id' => $file->id(),
      'entity_type' => 'file',
      'uri' => $file->getFileUri(),
    ];

    return $this->cropStorage->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCrop(CropInterface $crop): void {
    $this->cropStorage->delete([$crop]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getAspectRatio(CropInterface $crop): ?string {
    return !empty($crop->anchor()) ? implode(':', $crop->size()) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getOrientation(CropInterface $crop): string {
    $size = $crop->size();
    return $size['width'] < $size['height'] ? self::ORIENTATION_PORTRAIT : self::ORIENTATION_LANDSCAPE;
  }

  /**
   * Saves a crop entity.
   *
   * @param array $crop_settings
   *   The crop settings.
   * @param \Drupal\crop\CropInterface $crop
   *   Crop entity for the given file.
   *
   * @return \Drupal\crop\CropInterface
   *   Saved crop entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function saveCrop(array $crop_settings, CropInterface $crop): CropInterface {
    $aspect_ratio = explode(':', $crop_settings['aspect_ratio']);
    $orientation = $crop_settings['orientation'] ?? self::ORIENTATION_LANDSCAPE;

    if ($orientation === self::ORIENTATION_PORTRAIT) {
      $aspect_ratio = array_reverse($aspect_ratio, FALSE);
    }

    $crop
      ->setSize($aspect_ratio[0], $aspect_ratio[1])
      ->save();

    return $crop;
  }

}
