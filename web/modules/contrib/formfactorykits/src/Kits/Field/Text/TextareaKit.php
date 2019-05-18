<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class TextareaKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class TextAreaKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'textarea';
  const TYPE = 'textarea';
}
