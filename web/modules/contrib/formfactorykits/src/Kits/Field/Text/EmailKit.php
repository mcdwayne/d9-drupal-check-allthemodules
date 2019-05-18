<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DefaultValueTrait;
use Drupal\formfactorykits\Kits\Traits\PatternTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;

/**
 * Class EmailKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class EmailKit extends FormFactoryKit {
  use DefaultValueTrait;
  use PatternTrait;
  use SizeTrait;
  const ID = 'email';
  const TYPE = 'email';
  const DEFAULT_VALUE_KEY = 'default_value';
  const SIZE_KEY = 'size';
  const PATTERN_KEY = 'pattern';
}
