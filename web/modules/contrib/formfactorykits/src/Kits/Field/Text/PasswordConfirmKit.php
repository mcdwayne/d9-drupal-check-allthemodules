<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DefaultValueTrait;
use Drupal\formfactorykits\Kits\Traits\PatternTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;

/**
 * Class PasswordConfirmKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class PasswordConfirmKit extends FormFactoryKit {
  use DefaultValueTrait;
  use SizeTrait;
  use PatternTrait;
  const ID = 'password_confirm';
  const TYPE = 'password_confirm';
  const SIZE_KEY = 'size';
  const PATTERN_KEY = 'pattern';
}
