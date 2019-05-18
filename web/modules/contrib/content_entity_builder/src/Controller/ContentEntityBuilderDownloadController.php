<?php

namespace Drupal\content_entity_builder\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\system\FileDownloadController;
use Drupal\Core\Archiver\ArchiveTar;

/**
 * Class ContentEntityBuilderDownloadController.
 *
 * @package Drupal\content_entity_builder\Controller
 */
class ContentEntityBuilderDownloadController implements ContainerInjectionInterface {

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * @param \Drupal\system\FileDownloadController $file_download_controller
   */
  public function __construct(FileDownloadController $file_download_controller) {
    $this->fileDownloadController = $file_download_controller;
	//\Drupal::logger('content_entity_builder')->notice('__construct');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      new FileDownloadController()
    );
  }

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport($name) {
	//\Drupal::logger('content_entity_builder')->notice('downloadExport');

    $request = new Request(['file' => $name .'.tar.gz']);
    return $this->fileDownloadController->download($request, 'temporary');
  }

}
