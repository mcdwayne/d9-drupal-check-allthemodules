<?php

namespace Drupal\dashboard_connector\Checker;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Checks for modules which should be disabled.
 */
class ModuleStateChecker extends CheckerBase {

  /**
   * Represents a enabled module state.
   *
   * @var bool
   */
  const MODULE_ENABLED = TRUE;

  /**
   * Represents a disabled module state.
   *
   * @var bool
   */
  const MODULE_DISABLED = FALSE;

  /**
   * The enabled modules and their alert levels.
   *
   * @var array
   */
  protected $enabledModules = [];

  /**
   * The disabled modules and their alert levels.
   *
   * @var array
   */
  protected $disabledModules = [
    'views_ui' => 'warning',
  ];

  /**
   * The moduler handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ModuleStateChecker constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translations service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    parent::__construct($string_translation);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getChecks() {
    $checks = [];
    foreach ($this->disabledModules as $module => $alert_level) {
      if ($this->moduleHandler->moduleExists($module) !== static::MODULE_DISABLED) {
        $checks[] = $this->buildCheck('module disabled', $module, sprintf('%s module is enabled', $module), $alert_level);
      }
    }

    foreach ($this->enabledModules as $module => $alert_level) {
      if ($this->moduleHandler->moduleExists($module) !== static::MODULE_ENABLED) {
        $checks[] = $this->buildCheck('module enabled', $module, sprintf('%s module is disabled', $module), $alert_level);
      }
    }

    return $checks;
  }

}
