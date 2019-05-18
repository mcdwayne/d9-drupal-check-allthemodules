<?php

namespace Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators;

/**
 * Class ImageResolutionUploadValidatorKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators
 */
class ImageResolutionUploadValidatorKit extends UploadValidatorKit {
  const CALLBACK = 'file_validate_image_resolution';
  const MAX_WIDTH_KEY = 'max_width';
  const MAX_HEIGHT_KEY = 'max_height';

  /**
   * @inheritdoc
   */
  public function getCallbackArguments() {
    return [
      $this->getMaxResolution(),
    ];
  }

  /**
   * @return string
   */
  public function getMaxResolution() {
    return sprintf('%dx%d', $this->getMaxWidth(), $this->getMaxHeight());
  }

  /**
   * @param int $default
   *
   * @return static
   */
  public function getMaxWidth($default = 0) {
    return $this->getContext(self::MAX_WIDTH_KEY, $default);
  }

  /**
   * @param int $maxWidth
   * @return static
   */
  public function setMaxWidth($maxWidth) {
    return $this->setContext(self::MAX_WIDTH_KEY, (int) $maxWidth);
  }

  /**
   * @param int $default
   *
   * @return static
   */
  public function getMaxHeight($default = 0) {
    return $this->getContext(self::MAX_HEIGHT_KEY, $default);
  }

  /**
   * @param int $maxHeight
   * @return static
   */
  public function setMaxHeight($maxHeight) {
    return $this->setContext(self::MAX_HEIGHT_KEY, (int) $maxHeight);
  }
}
