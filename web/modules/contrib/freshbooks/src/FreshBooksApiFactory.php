<?php
/**
 * (c) MagnaX Software
 */

namespace Drupal\freshbooks;


use Drupal;
use Drupal\Core\Extension\MissingDependencyException;
use Freshbooks\FreshBooksApi;

class FreshBooksApiFactory {

  /**
   * @var bool
   */
  protected static $isLoaded;

  /**
   * @var array
   */
  protected static $library;

  /**
   * @return bool
   */
  public static function isFreshBooksApiLoaded() {
    return static::$isLoaded;
  }

  public static function loadFreshBooksApi() {
    return static::$isLoaded = (static::$library = libraries_load('freshbooks-api')) && static::$library['loaded'] !== FALSE;
  }

  /**
   * @return \Freshbooks\FreshBooksApi
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public static function createFreshBooksApiFromSettings() {
    if (!static::isFreshBooksApiLoaded()) {
      if (!static::loadFreshBooksApi()) {
        throw new MissingDependencyException('Could not load the FreshBooks API library: ' . static::$library['error message']);
      }
    }

    $config = Drupal::config('freshbooks.settings');

    $freshbooksApi = new FreshBooksApi($config->get('domain'), $config->get('token'));

    return $freshbooksApi;
  }
}
