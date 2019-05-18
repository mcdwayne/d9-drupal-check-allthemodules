<?php

namespace Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\kits\Services\KitsInterface;

/**
 * Class UploadValidatorKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators
 */
class UploadValidatorKit extends FormFactoryKit {
  const CALLBACK_KEY = 'callback';
  const CALLBACK = NULL;
  const CALLBACK_ARGUMENTS_KEY = 'callback_arguments';
  const CALLBACK_ARGUMENTS = [];

  /**
   * @return array
   */
  public function getCallbackArguments() {
    return $this->getContext(self::CALLBACK_ARGUMENTS_KEY, []);
  }

  /**
   * @param array $args
   *
   * @return static
   */
  public function setCallbackArguments(array $args = []) {
    return $this->setContext(self::CALLBACK_ARGUMENTS_KEY, $args);
  }
}
