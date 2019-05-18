<?php

namespace Drupal\formfactorykits\Kits\Field\Entity;

use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class UserAutoCompleteKit
 *
 * @package Drupal\formfactorykits\Kits\Entity
 */
class UserAutoCompleteKit extends EntityAutoCompleteKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'user_autocomplete';
  const TARGET_TYPE = 'user';
  const TITLE = 'User';
}
