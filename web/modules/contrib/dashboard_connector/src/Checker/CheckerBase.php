<?php

namespace Drupal\dashboard_connector\Checker;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * The CheckerBase class.
 */
abstract class CheckerBase implements CheckerInterface {

  use StringTranslationTrait;

  /**
   * PerformanceChecker constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translations service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Build an individual check array.
   *
   * @param string $type
   *   The type of error we're building.
   * @param string $module
   *   The module name.
   * @param string $description
   *   The error description.
   * @param string $alert_level
   *   The alert level.
   *
   * @return array
   *   A well formatted check.
   */
  protected function buildCheck($type, $module, $description, $alert_level) {
    return [
      'type' => $type,
      'name' => $module,
      'description' => $description,
      'alert_level' => $alert_level,
    ];
  }

}
