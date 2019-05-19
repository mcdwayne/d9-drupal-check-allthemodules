<?php

namespace Drupal\sir_trevor\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

class ImageStorage extends ControllerBase {

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function save() {
    $images = $_POST['images'];
    $extensionGuesser = ExtensionGuesser::getInstance();

    $baseFileName = date_format(new \DateTime(), 'Y-m-d-h-i-s');

    foreach ($images as $id => $image) {
      $mimeStart = strlen('data:');
      $mimeEnd = strpos($image, ';base64');
      $mime = substr($image, $mimeStart, $mimeEnd - $mimeStart);

      $extension = $extensionGuesser->guess($mime);
      $filename = "public://contentbuilder/{$baseFileName}-{$id}.{$extension}";

      $file = file_save_data($image, $filename);
      $images[$id] = $file;
    }

    $command = new ReplaceCommand('#img-1', $file->render);
    $i = 1;

    return new AjaxResponse();
  }
}
