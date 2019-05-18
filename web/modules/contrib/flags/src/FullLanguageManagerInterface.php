<?php
/**
 * Created by PhpStorm.
 * User: filip
 * Date: 22.01.2016
 * Time: 18:01
 */
namespace Drupal\flags;


/**
 *
 * The purpose of this service is to provide features that are missing from
 * ConfigurableLanguageManager.
 *
 * TODO: Consider extending \Drupal\language\ConfigurableLanguageManager and
 * replacing language_manager service
 *
 * @package Drupal\flags
 */
interface FullLanguageManagerInterface {

  /**
   * Returns list of ALL languages including predefined and configured.
   *
   * @return array
   */
  public function getAllDefinedLanguages();
}
