<?php

namespace Drupal\formfactorykits\Kits\Field\Media\ManagedFile;

use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\UploadValidatorKit;
use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\MultipleTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class FileKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Media\ManagedFile
 */
class FileKit extends FormFactoryKit {
  use DescriptionTrait;
  use MultipleTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'managed_file';
  const TYPE = 'managed_file';
  const MULTIPLE_KEY = 'multiple';
  const UPLOAD_LOCATION_KEY = 'upload_location';
  const UPLOAD_VALIDATORS_KEY = 'upload_validators';

  /**
   * @param string $location
   *
   * @return static
   */
  public function setUploadLocation($location) {
    return $this->set(self::UPLOAD_LOCATION_KEY, $location);
  }

  /**
   * @return array
   */
  public function getUploadValidators() {
    return $this->get(self::UPLOAD_VALIDATORS_KEY, []);
  }

  /**
   * @param array $validators
   *
   * @return static
   */
  public function setUploadValidators(array $validators) {
    return $this->set(self::UPLOAD_VALIDATORS_KEY, $validators);
  }

  /**
   * @param UploadValidatorKit $validator
   *
   * @return static
   */
  public function setUploadValidator(UploadValidatorKit $validator) {
    $validators = $this->getUploadValidators();
    $validators[$validator::CALLBACK] = $validator->getCallbackArguments();
    return $this->setUploadValidators($validators);
  }
}
