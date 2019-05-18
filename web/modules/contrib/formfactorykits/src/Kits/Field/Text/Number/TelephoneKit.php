<?php

namespace Drupal\formfactorykits\Kits\Field\Text\Number;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\PatternTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class TelephoneKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text\Number
 */
class TelephoneKit extends FormFactoryKit {
  use DescriptionTrait;
  use PatternTrait;
  use SizeTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'telephone';
  const TYPE = 'tel';
  const PATTERN_KEY = 'pattern';
  const SIZE_KEY = 'size';
  const TITLE = 'Phone';
}
