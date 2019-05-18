<?php

namespace Drupal\path_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\path_file\Entity\PathFileEntityInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystem;

/**
 * An example controller.
 */
class PathFileController extends ControllerBase {

  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(FileSystem $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function file(PathFileEntityInterface $path_file_entity) {

    $fid = $path_file_entity->getFid();
    $file = File::load($fid);
    $uri = $file->getFileUri();
    $server_path = $this->fileSystem->realpath($uri);

    return new BinaryFileResponse($server_path);
  }

}
