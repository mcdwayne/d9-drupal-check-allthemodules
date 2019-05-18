<?php

namespace Drupal\google_vision;

use Drupal\Core\File\FileSystem;
use Drupal\Component\Utility\Html;
use Drupal\google_vision\GoogleVisionApiInterface;


/**
 * Defines GoogleVisionHelper service.
 */
class GoogleVisionHelper implements GoogleVisionHelperInterface {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The GoogleVisionApi object.
   *
   * @var \Drupal\google_vision\GoogleVisionApiInterface
   */
  protected $googleVisionApi;

  /**
   * Construct an AutoAltTextFill object.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   *
   * @param \Drupal\google_vision\GoogleVisionApiInterface $google_vision_api
   *   A GoogleVisionApi object.
   */
  public function __construct(FileSystem $file_system, GoogleVisionApiInterface $google_vision_api) {
    $this->fileSystem = $file_system;
    $this->googleVisionApi = $google_vision_api;
  }

  /**
   * {@inheritdoc}
   */
  public function setAltText($file, $field) {
    // Initialize the output.
    $output = '';
    $settings = $field->getThirdPartySettings('google_vision');
    if (!empty($settings['alt_auto_filling'])) {
      $option = $settings['alt_auto_filling'];

      $file_uri = $file->getFileUri();
      if ($filepath = $this->fileSystem->realpath($file_uri)) {
        switch ($option) {
          case 'labels':
            $data = $this->googleVisionApi->labelDetection($filepath);
            // If we have retrieved labels.
            if (!empty($data['responses'][0]['labelAnnotations'])) {
              $output = $data['responses'][0]['labelAnnotations'];
            }
            break;

          case 'landmark':
            $data = $this->googleVisionApi->landmarkDetection($filepath);
            // If we have retrieved landmark.
            if (!empty($data['responses'][0]['landmarkAnnotations'])) {
              $output = $data['responses'][0]['landmarkAnnotations'];
            }
            break;

          case 'logo':
            $data = $this->googleVisionApi->logoDetection($filepath);
            // If we have retrieved logo.
            if (!empty($data['responses'][0]['logoAnnotations'])) {
              $output = $data['responses'][0]['logoAnnotations'];
            }
            break;

          case 'ocr':
            $data = $this->googleVisionApi->opticalCharacterRecognition($filepath);
            // If we have retrieved character.
            if (!empty($data['responses'][0]['textAnnotations'])) {
              $output = $data['responses'][0]['textAnnotations'];
            }
            break;

          case 'none':
            // If none is selected, do nothing.
            break;
        }
      }
      // If we have some data.
      if (!empty($output)) {
        // Grab first value (most relevant) and use it.
        $value = reset($output)['description'];
        $file->set($field->getName(), $value);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function editAltText($file, $field, $value) {
    //Set the new value to the Alt Text field.
    $file->set($field->getName(), $value);
  }

}
