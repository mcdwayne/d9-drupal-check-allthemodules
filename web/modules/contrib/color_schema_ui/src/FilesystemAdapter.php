<?php

namespace Drupal\color_schema_ui;

use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Drupal\Core\File\FileSystem as DrupalFilesystem;


class FilesystemAdapter {

  /**
   * @var SymfonyFilesystem
   */
  private $symfonyFilesystem;

  /**
   * @var DrupalFilesystem
   */
  private $drupalFilesystem;

  public function __construct(DrupalFilesystem $drupalFilesystem)
  {
    $this->symfonyFilesystem = new SymfonyFilesystem();
    $this->drupalFilesystem = $drupalFilesystem;
  }

  /**
   * @return SymfonyFilesystem
   */
  public function getSymfonyFilesystem(): SymfonyFilesystem {
    return $this->symfonyFilesystem;
  }

  /**
   * @return DrupalFilesystem
   */
  public function getDrupalFilesystem(): DrupalFilesystem {
    return $this->drupalFilesystem;
  }

  public function getFileContents(string $filepath): string {
    return file_get_contents($filepath);
  }

}
