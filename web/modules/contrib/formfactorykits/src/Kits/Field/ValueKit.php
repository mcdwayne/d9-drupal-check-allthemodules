<?php

namespace Drupal\formfactorykits\Kits\Field;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class ValueKit
 *
 * @package Drupal\formfactorykits\Kits\Field
 */
class ValueKit extends FormFactoryKit {
  use ValueTrait;
  const ID = 'value';
  const TYPE = 'value';
}
