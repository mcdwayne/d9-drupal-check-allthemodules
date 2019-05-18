<?php

namespace Drupal\formfactorykits\Kits\Field\Color;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class ColorKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Color
 */
class ColorKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'color';
  const TYPE = 'color';
}
