<?php

namespace Drupal\formfactorykits\Kits\Traits;

use Drupal\formfactorykits\Kits\Field\Common\OptionKit;

/**
 * Trait OptionsTrait
 *
 * @package Drupal\formfactorykits\Kits\Traits
 */
trait OptionsTrait {
  /**
   * @var OptionKit[]
   */
  public $options;

  /**
   * @param array $options
   *
   * @return static
   */
  public function setOptions(array $options) {
    foreach ($options as $key => $option) {
      if (!$option instanceof OptionKit) {
        $options[$key] = OptionKit::create($this->kitsService)
          ->setID($key)
          ->setTitle($option);
      }
    }
    $this->options = $options;
    return $this;
  }

  /**
   * @param OptionKit|array $mixed
   * @return static
   */
  public function appendOption($mixed) {
    if ($mixed instanceof OptionKit) {
      $this->options[] = $mixed;
    }
    elseif (is_array($mixed)) {
      $this->options[] = OptionKit::create($this->kitsService)
        ->setID(key($mixed))
        ->setTitle($mixed[key($mixed)]);
    }
    elseif (is_string($mixed)) {
      $this->options[] = OptionKit::create($this->kitsService)
        ->setID($mixed)
        ->setTitle($this->t($mixed));
    }
    return $this;
  }
}
