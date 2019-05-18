<?php

namespace Drupal\formfactorykits\Kits\Field\Media\ManagedFile;

use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\ImageExtensionUploadValidatorKit;
use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\ImageResolutionUploadValidatorKit;
use Drupal\kits\Services\KitsInterface;

/**
 * Class ImageKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Media
 */
class ImageKit extends FileKit {
  const ID = 'managed_image';

  /**
   * @inheritdoc
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    parent::__construct($kitsService, $id, $parameters, $context);
    $this->setUploadValidator(ImageExtensionUploadValidatorKit::create($kitsService));
  }

  /**
   * @param array $extensions
   *
   * @return static
   */
  public function setValidExtensions(array $extensions) {
    return $this->setUploadValidator(ImageExtensionUploadValidatorKit::create($this->kitsService)
      ->setExtensions($extensions));
  }

  /**
   * @param int $width
   * @param int $height
   *
   * @return static
   */
  public function setMaxResolution($width, $height) {
    return $this->setUploadValidator(ImageResolutionUploadValidatorKit::create($this->kitsService)
      ->setMaxWidth($width)
      ->setMaxHeight($height));
  }
}
