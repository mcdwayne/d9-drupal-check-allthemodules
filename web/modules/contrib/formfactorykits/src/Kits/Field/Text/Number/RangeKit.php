<?php

namespace Drupal\formfactorykits\Kits\Field\Text\Number;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DefaultValueTrait;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;

/**
 * Class RangeKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text\Number
 */
class RangeKit extends FormFactoryKit {
  use DefaultValueTrait;
  use DescriptionTrait;
  use TitleTrait;
  const ID = 'range';
  const TYPE = 'range';
  const TITLE = 'Range';
  const MINIMUM_KEY = 'min';
  const MAXIMUM_KEY = 'max';
  const STEP_KEY = 'step';

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
}
