<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\PatternTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class UrlKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class UrlKit extends FormFactoryKit {
  use DescriptionTrait;
  use PatternTrait;
  use SizeTrait;
  use ValueTrait;
  const ID = 'url';
  const TYPE = 'url';
  const DEFAULT_VALUE_KEY = 'default_value';
  const SIZE_KEY = 'size';
  const PATTERN_KEY = 'pattern';
  const TITLE = 'URL';
}
