<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;

/**
 * Class SearchKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class SearchKit extends FormFactoryKit {
  use TitleTrait;
  const ID = 'search';
  const TYPE = 'search';
}
