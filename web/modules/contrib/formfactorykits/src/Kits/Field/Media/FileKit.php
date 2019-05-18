<?php

namespace Drupal\formfactorykits\Kits\Field\Media;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\MultipleTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class FileKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Media
 */
class FileKit extends FormFactoryKit {
  use DescriptionTrait;
  use MultipleTrait;
  use SizeTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'file';
  const TYPE = 'file';
  const MULTIPLE_KEY = 'multiple';
  const SIZE_KEY = 'size';
}
