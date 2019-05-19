<?php

namespace Drupal\system_stream_wrapper\StreamWrapper;

/**
 * Defines the read-only theme:// stream wrapper for theme files.
 */
class ThemeStream extends ExtensionStreamBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName() {
    $name = parent::getOwnerName();
    if (!$this->getThemeHandler()->themeExists($name)) {
      // The theme does not exist or is not installed.
      throw new \InvalidArgumentException("Theme $name does not exist or is not installed");
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->getThemeHandler()->getTheme($this->getOwnerName())->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Theme files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Local files stored under theme directory.');
  }

  /**
   * Returns the theme handler service.
   *
   * @return \Drupal\Core\Extension\ThemeHandlerInterface
   *   The theme handler service.
   */
  protected function getThemeHandler() {
    if (!isset($this->themeHandler)) {
      $this->themeHandler = \Drupal::service('theme_handler');
    }
    return $this->themeHandler;
  }

}
