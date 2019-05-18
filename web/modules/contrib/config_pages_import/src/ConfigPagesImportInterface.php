<?php

namespace Drupal\config_pages_import;


/**
 * Interface ConfigPagesImportInterface
 *
 * @package Drupal\config_pages_import
 */
interface ConfigPagesImportInterface
{

  /**
   * Import config entities from module
   *
   * @param string $moduleName
   *
   * @return void
   */
  public function importFromModule(string $moduleName);

  /**
   * Import config entity
   *
   * @param string $configEntityName
   *
   * @return void
   */
  public function import(string $configEntityName);


}