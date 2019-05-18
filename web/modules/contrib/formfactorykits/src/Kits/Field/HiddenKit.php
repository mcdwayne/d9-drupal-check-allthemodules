<?php

namespace Drupal\formfactorykits\Kits\Field;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class HiddenKit
 *
 * @package Drupal\formfactorykits\Kits\Field
 */
class HiddenKit extends FormFactoryKit {
  use ValueTrait;
  const ID = 'hidden';
  const TYPE = 'hidden';
}
