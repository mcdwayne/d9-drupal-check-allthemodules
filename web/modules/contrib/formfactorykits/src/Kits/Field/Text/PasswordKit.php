<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DefaultValueTrait;
use Drupal\formfactorykits\Kits\Traits\PatternTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;

/**
 * Class PasswordKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class PasswordKit extends FormFactoryKit {
  use DefaultValueTrait;
  use SizeTrait;
  use PatternTrait;
  const ID = 'password';
  const TYPE = 'password';
  const TITLE = 'Password';
  const SIZE_KEY = 'size';
  const PATTERN_KEY = 'pattern';
}
