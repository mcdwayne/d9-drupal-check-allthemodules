<?php

namespace Drupal\unused_modules;

use Drupal\Core\Extension\Extension;

/**
 * Decorates the core Extension object.
 */
class UnusedModulesExtensionDecorator extends Extension {

  /**
   * The Extension object.
   *
   * @var \Drupal\Core\Extension\Extension
   */
  protected $extension;

  /**
   * TRUE if the module is enabled. Defaults to FALSE.
   *
   * @var bool
   */
  public $moduleIsEnabled = FALSE;

  /**
   * Basepath of the project.
   *
   * @var string
   */
  public $projectPath = '';

  /**
   * Name of the project.
   *
   * @var string
   */
  public $projectName = '';

  /**
   * TRUE if the project contains enabled modules. Defaults to FALSE.
   *
   * @var bool
   */
  public $projectHasEnabledModules = FALSE;

  /**
   * TRUE if the parser fails.
   *
   * @var bool
   */
  public $parsingError = FALSE;

  /**
   * UnusedModulesExtensionDecorator constructor.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The Extension object.
   */
  public function __construct(Extension $extension) {
    $this->extension = $extension;
  }

  /**
   * Inherited from Extension.
   */
  public function getPathname() {
    return $this->extension->getPathname();
  }

  /**
   * Inherited from Extension.
   */
  public function getName() {
    return $this->extension->getName();
  }

  /**
   * Inherited from Extension.
   */
  public function getSubpath() {
    return $this->extension->subpath;
  }

  /**
   * Inherited from Extension.
   */
  public function getOrigin() {
    return $this->extension->origin;
  }

}
