<?php

namespace Drupal\tmgmt_memory\Controller;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to export usages.
 */
class ExportController implements ContainerInjectionInterface {

  /**
   * The EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      new FileDownloadController()
    );
  }

  /**
   * Constructs a ExportController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The source storage
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FileDownloadController $file_download_controller) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileDownloadController = $file_download_controller;
  }

  /**
   * Downloads a tarball of with the translations.
   */
  public function downloadExport() {
    file_unmanaged_delete(file_directory_temp() . '/tmgmt_memory.tar.gz');

    $archiver = new ArchiveTar(file_directory_temp() . '/tmgmt_memory.tar.gz', 'gz');

    $segment_translations = \Drupal::entityTypeManager()->getStorage('tmgmt_memory_segment_translation')->loadMultiple();
    $languages = array_unique(array_map(function (&$value){
      /** @var \Drupal\tmgmt_memory\SegmentTranslationInterface $value */
      return $value->getSource()->getLangcode();
    }, $segment_translations));
    /** @var \Drupal\tmgmt_memory\Tmx $export */
    $export = \Drupal::service('tmgmt_memory.tmx');
    foreach ($languages as $language) {
      $name = 'TMGMT_Memory' . '_' . $language . '.tmx';
      $archiver->addString($name, $export->export($language));
    }

    $request = new Request(array('file' => 'tmgmt_memory.tar.gz'));
    return $this->fileDownloadController->download($request, 'temporary');
  }

}
