<?php

namespace Drupal\image_edit\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystem;
use Drupal\file\Entity\File;
use Drupal\Tests\system\Functional\Render\AjaxPageStateTest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ReplaceImage extends ControllerBase {
  /** @var FileSystem */
  protected $fileSystem;

  public function __construct(FileSystem $fileSystem) {
    $this->fileSystem = $fileSystem;
  }

  public function replaceImage(Request $request, $fileId) {
    $image = File::load($fileId);

    if (is_null($image)) {
      return new AjaxResponse(['msg' => t('Image not found.')]);
    }

    $file = new \SplFileObject(
      $this->fileSystem->realpath($image->getFileUri()), 'wb'
    );
    $fileContent = base64_decode($request->getContent());

    if (false === $fileContent) {
      return new AjaxResponse(0, 404);
    }

    $bytesWritten = $file->fwrite($fileContent);
    image_path_flush($image->getFileUri());

    return new AjaxResponse($bytesWritten);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var FileSystem $fileSystem */
    $fileSystem = $container->get('file_system');

    return new self($fileSystem);
  }
}