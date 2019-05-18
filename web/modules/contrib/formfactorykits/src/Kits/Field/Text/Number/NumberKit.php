<?php

namespace Drupal\formfactorykits\Kits\Field\Text\Number;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DefaultValueTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;

/**
 * Class NumberKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text\Number
 */
class NumberKit extends FormFactoryKit {
  use DefaultValueTrait;
  use TitleTrait;
  const ID = 'number';
  const TYPE = 'number';
  const MINIMUM_KEY = 'min';
  const MAXIMUM_KEY = 'max';
  const STEP_KEY = 'step';
  const SIZE_KEY = 'size';

  /**
   * @param int $min
   *
   * @return static
   */
  public function setMinimum($min) {
    return $this->set(self::MINIMUM_KEY, (int) $min);
  }

  /**
   * @param int $max
   *
   * @return static
   */
  public function setMaximum($max) {
    return $this->set(self::MAXIMUM_KEY, (int) $max);
  }

  /**
   * @param int $step
   *
   * @return static
   */
  public function setStep($step) {
    return $this->set(self::STEP_KEY, (int) $step);
  }

  /**
   * @param int $size
   *
   * @return static
   */
  public function setSize($size) {
    return $this->set(self::SIZE_KEY, (int) $size);
  }
}
