<?php

namespace Drupal\dashboard_connector\Checker;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Checker that exports the enabled core extensions.
 */
class CoreExtensionChecker extends CheckerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * CoreModuleChecker constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($string_translation);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getChecks() {
    $checks = [];
    $extensions = $this->moduleHandler->getModuleList();
    foreach ($extensions as $extension) {
      if (substr($extension->getPathname(), 0, 4) == 'core') {
        $type = $extension->getType();
        $checks[] = $this->buildCheck("core_$type", $extension->getName(), "Enabled $type", 'notice');
      }
    }
    return $checks;
  }

}
