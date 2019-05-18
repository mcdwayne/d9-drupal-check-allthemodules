<?php

namespace Drupal\color_schema_ui;

use Drupal\Core\Theme\ThemeManager;
use Drupal\Core\Asset\CssCollectionOptimizer;


class SCSSCompilerFacade {

  /**
   * @var SCSSCompiler
   */
  private $SCSSCompilerAdapter;

  /**
   * @var FilesystemAdapter
   */
  private $filesystemAdapter;

  /**
   * @var CssCollectionOptimizer
   */
  private $cssCollectionOptimizer;

  /**
   * @var string
   */
  private $CSSDestinationFilepath;

  /**
   * @var string
   */
  private $SCSSDestinationFilepath;

  /**
   * @var string
   */
  private $initialSCSSDestinationFilepath;

  public function __construct(SCSSCompiler $SCSSCompilerAdapter, ThemeManager $themeManager, FilesystemAdapter $filesystemAdapter, CssCollectionOptimizer $cssCollectionOptimizer) {
    $this->SCSSCompilerAdapter = $SCSSCompilerAdapter;
    $this->filesystemAdapter = $filesystemAdapter;
    $this->cssCollectionOptimizer = $cssCollectionOptimizer;

    $this->CSSDestinationFilepath = $this->filesystemAdapter->getDrupalFilesystem()->realpath('public://') . '/color_schema_ui.css';
    $this->SCSSDestinationFilepath = $this->filesystemAdapter->getDrupalFilesystem()->realpath('public://') . '/color_schema_ui.scss';
    $activeThemePath = $themeManager->getActiveTheme()->getPath();
    $this->initialSCSSDestinationFilepath = DRUPAL_ROOT . '/' . $activeThemePath . '/source/sass/color_schema_ui.scss';

    if (!\file_exists($this->initialSCSSDestinationFilepath)) {
      $this->initialSCSSDestinationFilepath = drupal_get_path('module', 'color_schema_ui') . '/templates/scss/color_schema_ui.scss';
    }
  }

  /**
   * @param null|\stdClass $colorsToReplace
   */
  public function compileSCSSToFilesystem($colorsToReplace = null): void {
    $cssDestinationFilepath = $this->CSSDestinationFilepath;

    $scssFileContents = $this->filesystemAdapter->getFileContents($this->SCSSDestinationFilepath);

    $compiledCSS = $this->SCSSCompilerAdapter->compile($scssFileContents, $colorsToReplace);

    $this->filesystemAdapter->getSymfonyFilesystem()->dumpFile($this->SCSSDestinationFilepath, $this->SCSSCompilerAdapter->getUpdatedSCSS());
    $this->filesystemAdapter->getSymfonyFilesystem()->dumpFile($cssDestinationFilepath, $compiledCSS);

    $this->cssCollectionOptimizer->deleteAll();
  }

  public function initialCompileSCSSToFilesystem(): void {
    $this->filesystemAdapter->getSymfonyFilesystem()->copy($this->initialSCSSDestinationFilepath, $this->SCSSDestinationFilepath);

    $cssDestinationFilepath = $this->CSSDestinationFilepath;

    $scssFileContents = $this->filesystemAdapter->getFileContents($this->SCSSDestinationFilepath);

    $compiledCSS = $this->SCSSCompilerAdapter->compile($scssFileContents);

    $this->filesystemAdapter->getSymfonyFilesystem()->dumpFile($cssDestinationFilepath, $compiledCSS);

    $this->cssCollectionOptimizer->deleteAll();
  }

  public function removeAssets(): void {
    $this->filesystemAdapter->getSymfonyFilesystem()->remove([
      $this->SCSSDestinationFilepath,
      $this->CSSDestinationFilepath
    ]);
  }

  /**
   * @param null|\stdClass $colorsToReplace
   * @return string
   */
  public function getCompiledSCSS($colorsToReplace = null): string {
    $scssFileContents = $this->filesystemAdapter->getFileContents($this->SCSSDestinationFilepath);

    return $this->SCSSCompilerAdapter->compile($scssFileContents, $colorsToReplace);
  }

  public function getInitialColors(): array {
    $scssFileContents = $this->filesystemAdapter->getFileContents($this->SCSSDestinationFilepath);

    return $this->SCSSCompilerAdapter->getInitialColors($scssFileContents);
  }

}
