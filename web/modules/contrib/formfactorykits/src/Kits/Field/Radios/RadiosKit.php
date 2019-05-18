<?php

namespace Drupal\formfactorykits\Kits\Field\Radios;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\OptionsTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class RadiosKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Radios
 */
class RadiosKit extends FormFactoryKit {
  use DescriptionTrait;
  use OptionsTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'radios';
  const TYPE = 'radios';
  const OPTIONS_KEY = 'options';

  /**
   * @inheritdoc
   */
  public function getArray() {
    $artifact = parent::getArray();
    if (isset($this->options)) {
      foreach ($this->options as $option) {
        $artifact['#' . self::OPTIONS_KEY][$option->getID()] = $option->getTitle();
      }
    }
    return $artifact;
  }
}
