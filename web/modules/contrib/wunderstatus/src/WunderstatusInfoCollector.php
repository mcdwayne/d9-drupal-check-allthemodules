<?php

namespace Drupal\wunderstatus;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Install\Tasks;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class WunderstatusInfoCollector {
  use StringTranslationTrait;

  /** @var ModuleHandlerInterface */
  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @return array Modules and core system versions. Includes:
   * - Drupal core version
   * - PHP version
   * - Database version
   */
  public function getVersionInfo() {
    $modules = $this->getNonCoreModules();

    $versions = [
      $this->getDrupalVersion(), 
      $this->getPhpVersion(),
      $this->getDatabaseSystemVersion()
    ];

    foreach ($modules as $module) {
      $versions[] = $module->getName() . ' ' . $this->getModuleVersion($module);
    }

    return $versions;
  }

  /**
   * @return Extension[]
   */
  private function getNonCoreModules() {
    $modules = $this->moduleHandler->getModuleList();

    return array_filter($modules, function ($module) {
      /** @var $module Extension */
      return strpos($module->getPathname(), 'core') !== 0;
    });
  }
  
  private function getPhpVersion() {
    return 'PHP ' . phpversion();
  }

  private function getDrupalVersion() {
    return 'Drupal ' . \Drupal::VERSION;
  }

  protected function getDatabaseSystemVersion() {
    $class = Database::getConnection()->getDriverClass('Install\\Tasks');
    /** @var $tasks Tasks */
    $tasks = new $class();

    return $tasks->name() . ' ' . Database::getConnection()->version();
  }

  private function getModuleVersion(Extension $module) {
    $infoFile = $this->getInfoFile($module);
    $version = $this->t('Unspecified');

    foreach ($infoFile as $lineNumber => $line) {
      if (strpos($line, 'version:') !== FALSE) {
        $version = $this->parseVersion($line);
      }
    }

    return $version;
  }
  
  /**
   * @param Extension $module
   * @return array
   */
  protected function getInfoFile(Extension $module) {
    return file($module->getPathname());
  }

  private function parseVersion($versionString) {
    $version = str_replace('version:', '', $versionString);
    $version = str_replace("'", '', $version);

    return trim($version);
  }
}