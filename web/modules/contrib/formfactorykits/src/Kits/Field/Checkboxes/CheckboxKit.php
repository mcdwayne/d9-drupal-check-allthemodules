<?php

namespace Drupal\formfactorykits\Kits\Field\Checkboxes;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class CheckboxKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Checkboxes
 */
class CheckboxKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'checkbox';
  const TYPE = 'checkbox';
}
