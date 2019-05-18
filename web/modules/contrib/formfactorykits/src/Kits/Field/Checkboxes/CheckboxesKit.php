<?php

namespace Drupal\formfactorykits\Kits\Field\Checkboxes;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\OptionsTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class CheckboxesKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Checkboxes
 */
class CheckboxesKit extends FormFactoryKit {
  use DescriptionTrait;
  use OptionsTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'checkboxes';
  const TYPE = 'checkboxes';
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
