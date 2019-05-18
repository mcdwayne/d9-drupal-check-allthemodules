<?php

namespace Drupal\formfactorykits\Kits\Field\Date;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class DateTimeKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Date
 */
class DateTimeKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'datetime';
  const TYPE = 'datetime';
  const DATE_INCREMENT_KEY = 'date_increment';
  const DATE_TIMEZONE_KEY = 'date_timezone';

  /**
   * @param string $increment
   *
   * @return static
   */
  public function setIncrement($increment) {
    return $this->set(self::DATE_INCREMENT_KEY, $increment);
  }

  /**
   * @param \DateTimeZone $timezone
   *
   * @return static
   */
  public function setTimeZone(\DateTimeZone $timezone) {
    return $this->set(self::DATE_TIMEZONE_KEY, $timezone->getName());
  }
}
