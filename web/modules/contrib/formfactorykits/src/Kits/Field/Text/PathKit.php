<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class PathKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class PathKit extends TextFieldKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'path';
  const TYPE = 'path';
  const TITLE = 'Path';
  const CONVERT_PATH_KEY = 'convert_path';
  const VALIDATE_PATH_KEY = 'validate_path';

  /**
   * Sets the field to convert the submitted value to the given setting.
   *
   * @param int $setting
   *   @see PathElement::CONVERT_NONE
   *   @see PathElement::CONVERT_ROUTE
   *   @see PathElement::CONVERT_URL
   *
   * @return static
   */
  public function setConversion($setting) {
    return $this->set(self::CONVERT_PATH_KEY, (int) $setting);
  }

  /**
   * @param bool $validate
   *
   * @return static
   */
  public function setValidate($validate = TRUE) {
    return $this->set(self::VALIDATE_PATH_KEY, (bool) $validate);
  }
}
