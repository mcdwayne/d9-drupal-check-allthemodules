<?php

namespace Drupal\formfactorykits\Kits\Field\Date;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class DateKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Date
 */
class DateKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'date';
  const TYPE = 'date';
}
