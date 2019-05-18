<?php

namespace Drupal\formfactorykits\Kits\Field\Select;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\OptionsTrait;
use Drupal\formfactorykits\Kits\Traits\TitleTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;
use Drupal\kits\Services\KitsInterface;

/**
 * Class SelectKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Select
 */
class SelectKit extends FormFactoryKit {
  use DescriptionTrait;
  use OptionsTrait;
  use TitleTrait;
  use ValueTrait;
  const ID = 'select';
  const TYPE = 'select';
  const OPTIONS_KEY = 'options';
  const EMPTY_OPTION_KEY = 'empty_option';
  const EMPTY_VALUE_KEY = 'empty_value';
  const MULTIPLE_KEY = 'multiple';
  const SIZE = 'size';

  /**
   * @inheritdoc
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!array_key_exists(self::EMPTY_OPTION_KEY, $parameters)) {
      $parameters[self::EMPTY_OPTION_KEY] = '';
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @param bool $isMultiple
   *
   * @return static
   */
  public function setMultiple($isMultiple = TRUE) {
    return $this->set(self::MULTIPLE_KEY, (bool) $isMultiple);
  }

  /**
   * @return bool
   */
  public function isMultiple() {
    return (bool) $this->get(self::MULTIPLE_KEY);
  }

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
