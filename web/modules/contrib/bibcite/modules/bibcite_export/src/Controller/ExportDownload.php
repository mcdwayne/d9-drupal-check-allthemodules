<?php

namespace Drupal\bibcite_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download a file created by "Export all" form.
 */
class ExportDownload extends ControllerBase {

  /**
   * Storage of the File entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Construct a new ExportDownload controller object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   Storage of the File entity.
   */
  public function __construct(EntityStorageInterface $file_storage) {
    $this->fileStorage = $file_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file')
    );
  }

  /**
   * Download file callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param \Drupal\file\FileInterface $file
   *   File to download.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object.
   */
  public function download(Request $request, FileInterface $file) {
    $response = new Response();

    $response->headers->set('Cache-Control', 'no-cache');
    $response->headers->set('Content-type', 'text/plain');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $file->getFilename() . '";');

    $response->setContent(file_get_contents($file->getFileUri()));

    return $response;
  }

}
