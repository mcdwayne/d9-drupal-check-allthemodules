<?php

namespace Drupal\formfactorykits\Kits\Container;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;

/**
 * Class DetailsKit
 *
 * @package Drupal\formfactorykits\Kits\Container
 */
class DetailsKit extends FormFactoryKit {
  use TitleTrait;

  const ID = 'details';
  const TYPE = 'details';
  const OPEN_KEY = 'open';

  /**
   * @param bool $isOpen
   *
   * @return static
   */
  public function setOpen($isOpen = TRUE) {
    return $this->set(self::OPEN_KEY, (bool) $isOpen);
  }
}
