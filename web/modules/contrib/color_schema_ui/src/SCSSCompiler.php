<?php

namespace Drupal\color_schema_ui;

use Leafo\ScssPhp\Compiler;


class SCSSCompiler {

  /**
   * @var Compiler
   */
  private $compiler;

  /**
   * @var SCSSColorHandler
   */
  private $scssColorHandler;
  /**
   * @var FilesystemAdapter
   */
  private $filesystemAdapter;

  /**
   * @var string
   */
  private $updatedSCSS;

  public function __construct(SCSSPhpCompilerFactory $SCSSPhpCompilerFactory, SCSSColorHandler $SCSSColorHandler, FilesystemAdapter $filesystemAdapter) {
    $this->compiler = $SCSSPhpCompilerFactory->create();
    $this->scssColorHandler = $SCSSColorHandler;
    $this->filesystemAdapter = $filesystemAdapter;
  }

  /**
   * @param string      $scss
   * @param null|array  $colorsReplacement
   * @return string
   */
  public function compile(string $scss, $colorsReplacement = null): string {
    if (\is_array($colorsReplacement)) {
      $scss = $this->scssColorHandler->replaceColors($scss, $colorsReplacement);
    }

    $this->setUpdatedSCSS($scss);

    return $this->compiler->compile($scss);
  }

  public function getInitialColors(string $scss): array {
    return $this->scssColorHandler->getInitialColors($scss);
  }

  /**
   * @return string
   */
  public function getUpdatedSCSS(): string
  {
    return $this->updatedSCSS;
  }

  /**
   * @param string $updatedSCSS
   */
  public function setUpdatedSCSS(string $updatedSCSS): void
  {
    $this->updatedSCSS = $updatedSCSS;
  }

}
