<?php

namespace Drupal\bigvideo\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a storage class for BigvieoSource entity.
 *
 * Control file usages.
 */
class BigvideoSourceStorage extends ConfigEntityStorage {

  /**
   * File usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, FileUsageInterface $file_usage) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);

    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    /** @var \Drupal\bigvideo\Entity\BigvideoSourceInterface $original */
    $original = $this->load($entity->getOriginalId());
    if ($original && $original->getType() == BigvideoSourceInterface::TYPE_FILE) {
      $this->deleteFileUsages($original);
    }

    /** @var \Drupal\bigvideo\Entity\BigvideoSourceInterface $entity */
    if ($entity->getType() == BigvideoSourceInterface::TYPE_FILE) {
      $this->addFileUsages($entity);
    }

    return parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    /** @var \Drupal\bigvideo\Entity\BigvideoSourceInterface $entity */
    foreach ($entities as $entity) {
      if ($entity->getType() == BigvideoSourceInterface::TYPE_FILE) {
        $this->deleteFileUsages($entity);
      }
    }

    parent::delete($entities);
  }

  /**
   * Delete files usages of source files.
   *
   * @param \Drupal\bigvideo\Entity\BigvideoSourceInterface $entity
   *   Source entity.
   */
  private function deleteFileUsages(BigvideoSourceInterface $entity) {
    if ($mp4 = $entity->getMp4()) {
      if (intval($mp4) && $mp4_file = File::load($mp4)) {
        $this->fileUsage->delete($mp4_file, 'bigvideo', 'bigvideo_source', $entity->id(), 0);
      }
    }

    if ($webm = $entity->getWebM()) {
      if (intval($webm) &&  $webm_file = File::load($webm)) {
        $this->fileUsage->delete($webm_file, 'bigvideo', 'bigvideo_source', $entity->id(), 0);
      }
    }
  }

  /**
   * Add files usages for source files.
   *
   * @param \Drupal\bigvideo\Entity\BigvideoSourceInterface $entity
   *   Source entity.
   */
  private function addFileUsages(BigvideoSourceInterface $entity) {
    if ($mp4 = $entity->getMp4()) {
      if (intval($mp4) && $mp4_file = File::load($mp4)) {
        $this->fileUsage->add($mp4_file, 'bigvideo', 'bigvideo_source', $entity->id());
      }
    }

    if ($webm = $entity->getWebM()) {
      if (intval($webm) &&  $webm_file = File::load($webm)) {
        $this->fileUsage->add($webm_file, 'bigvideo', 'bigvideo_source', $entity->id());
      }
    }
  }

}
