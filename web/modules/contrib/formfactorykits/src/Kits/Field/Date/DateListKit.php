<?php

namespace Drupal\formfactorykits\Kits\Field\Date;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class DateListKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Date
 */
class DateListKit extends FormFactoryKit {
  use DescriptionTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'datelist';
  const TYPE = 'datelist';
  const DATE_INCREMENT_KEY = 'date_increment';
  const DATE_PART_ORDER_KEY = 'date_part_order';

  /**
   * @param string $increment
   *
   * @return static
   */
  public function setIncrement($increment) {
    return $this->set(self::DATE_INCREMENT_KEY, $increment);
  }

  /**
   * @param string $order
   *
   * @return static
   */
  public function setDatePartOrder($order) {
    return $this->set(self::DATE_PART_ORDER_KEY, $order);
  }
}
