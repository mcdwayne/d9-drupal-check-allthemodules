<?php

namespace Drupal\Tests\color_schema_ui\Kernel;

use Drupal\color_schema_ui\FilesystemAdapter;
use Drupal\KernelTests\KernelTestBase;


class InstallTest extends KernelTestBase {

  /**
   * @var array
   */
  public static $modules = [
    'system',
    'color_schema_ui'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['color_schema_ui']);
  }

  public function testCompileOnInstall(): void {
    /**
     * @var \Drupal\color_schema_ui\SCSSCompilerFacade $SCSSCompilerFacade
     */
    $SCSSCompilerFacade = \Drupal::service('color_schema_ui.scss_compiler_facade');
    $SCSSCompilerFacade->initialCompileSCSSToFilesystem();

    /**
     * @var FilesystemAdapter $filesystemAdapter
     */
    $filesystemAdapter = \Drupal::service('color_schema_ui.filesystem_adapter');
    self::assertNotEmpty($filesystemAdapter->getFileContents($filesystemAdapter->getDrupalFilesystem()->realpath('public://') . '/color_schema_ui.css'));
    self::assertNotEmpty($filesystemAdapter->getFileContents($filesystemAdapter->getDrupalFilesystem()->realpath('public://') . '/color_schema_ui.scss'));
  }

}
