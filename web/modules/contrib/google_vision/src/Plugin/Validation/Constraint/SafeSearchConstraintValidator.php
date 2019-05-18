<?php

namespace Drupal\google_vision\Plugin\Validation\Constraint;

use Drupal\google_vision\GoogleVisionApiInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Validates the SafeSearch constraint.
 */
class SafeSearchConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Google Vision Api.
   *
   * @var \Drupal\google_vision\GoogleVisionApiInterface.
   */
  protected $googleVisionApi;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface.
   */
  protected $fileSystem;

  /**
   * Constructs a SafeSearchConstraintValidator object.
   *
   * @param \Drupal\google_vision\GoogleVisionApiInterface $googleVisionApi
   *  The Google Vision API object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *  The File System object.
   */
  public function __construct(GoogleVisionApiInterface $google_vision_api, FileSystemInterface $file_system) {
    $this->googleVisionApi = $google_vision_api;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('google_vision.api'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($data, Constraint $constraint) {
    $field_def = $data->getFieldDefinition();
    $settings = $field_def->getThirdPartySettings('google_vision');
    // If the Safe Search detection is on.
    if (!empty($settings['safe_search'])) {
      // If the image is uploaded.
      $value = $data->getValue('target_id');
      if (!empty($value)) {
        // Retrieve the file uri.
        $file_uri = $data->entity->getFileUri();
        if ($filepath = $this->fileSystem->realpath($file_uri)) {
          $result = $this->googleVisionApi->safeSearchDetection($filepath);
          if (!empty($result['responses'][0]['safeSearchAnnotation'])) {
            $adult = $result['responses'][0]['safeSearchAnnotation']['adult'];
            $likelihood = array('POSSIBLE', 'LIKELY', 'VERY_LIKELY');
            // If the image has explicit content.
            if (in_array($adult, $likelihood)) {
              $this->context->addViolation($constraint->message);
            }
          }
        }
      }
    }
  }

}
