<?php

namespace Drupal\Tests\streamy_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for streamy_ui tests.
 */
abstract class StreamyUITestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['streamy_ui'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Gets public files Drupal folder with trailing slash.
   *
   * @param bool $absolute
   *    Returns a full absolute path from the root of your drive.
   * @return string
   */
  public function getPublicFilesDirectory($absolute = FALSE) {
    if ($absolute) {
      $public_folder = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      return rtrim($public_folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
    $streamWrapperPublic = \Drupal::service('stream_wrapper.public');
    return rtrim($streamWrapperPublic->basePath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
  }
}
