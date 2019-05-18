<?php

namespace Drupal\formfactorykits\Kits\Button;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\AjaxTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;
use Drupal\kits\Services\KitsInterface;

/**
 * Class ButtonKit
 *
 * @package Drupal\formfactorykits\Kits
 */
class ButtonKit extends FormFactoryKit {
  use AjaxTrait;
  use ValueTrait;
  const ID = 'button';
  const TYPE = 'button';
  const VALUE = NULL;
  const BUTTON_TYPE_KEY = 'button_type';

  /**
   * @inheritdoc
   */
  public function __construct(KitsInterface $kitsService,
                              $id = NULL,
                              array $parameters = [],
                              array $context = []) {
    if (!isset($parameters[self::VALUE_KEY]) && !empty(static::VALUE)) {
      $parameters[self::VALUE_KEY] = $kitsService->t(static::VALUE);
    }
    parent::__construct($kitsService, $id, $parameters, $context);
  }

  /**
   * @param string $type
   *
   * @return static
   */
  public function setButtonType($type) {
    return $this->set(self::BUTTON_TYPE_KEY, $type);
  }
}
