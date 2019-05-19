<?php

namespace Drupal\system_stream_wrapper\StreamWrapper;

/**
 * Defines the read-only module:// stream wrapper for module files.
 */
class ModuleStream extends ExtensionStreamBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName() {
    $name = parent::getOwnerName();
    if (!$this->getModuleHandler()->moduleExists($name)) {
      // The module does not exist or is not installed.
      throw new \InvalidArgumentException("Module $name does not exist or is not installed");
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->getModuleHandler()->getModule($this->getOwnerName())->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Module files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Local files stored under module directory.');
  }

  /**
   * Returns the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected function getModuleHandler() {
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

}
