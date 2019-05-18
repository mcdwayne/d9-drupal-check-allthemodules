<?php

namespace Drupal\md_site_verify\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class DomainSiteVerifyService.
 *
 * @package Drupal\md_site_verify\Service.
 */
class DomainSiteVerifyService {

  /**
   * @var \Drupal\Core\Database\Connection $database
   */
  protected $database;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a DomainSiteVerifyService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database, ModuleHandlerInterface $moduleHandler) {
    $this->database = $database;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Menu load callback; loads a site verification record.
   *
   * This also loads the engine details if the record was found.
   *
   * @param int $svid
   *   A site verification ID.
   *
   * @return array
   *   An array of the site verification record, or FALSE if not found.
   */
  public function domainSiteVerifyLoad($dsvid) {
    $record = $this->database->select('md_site_verify', 'dsv')
      ->fields('dsv')
      ->condition('dsv_id', $dsvid)
      ->execute()
      ->fetchAssoc();
    if ($record) {
      $record['engine'] = $this->domainSiteVerifyEngineLoad($record['engine']);
    }
    return $record;
  }

  /**
   * Check acces to a route with domain id requirement.
   *
   * @param string $domainId
   *   The domain id.
   *
   * @return bool
   */
  public function domainSiteVerifyAccessCheck($domainId) {
    $domain = $this->database->select('md_site_verify', 'dsv')
      ->fields('dsv')
      ->condition('domain_id', $domainId)
      ->condition('file', "", '<>')
      ->execute()
      ->fetchField();
    return $domain ? $domain : FALSE;
  }

  /**
   * Check if Meta tags exist.
   *
   * @param string $domainId
   *
   * @return bool
   *   Return domain if exist, or FALSE.
   */
  public function domainSiteVerifyMetaTags($domainId) {
    $existing_metatag = $this->database->select('md_site_verify', 'dsv')
      ->fields('dsv', ['domain_id'])
      ->condition('domain_id', $domainId)
      ->execute()
      ->fetchField();
    return $existing_metatag ? $existing_metatag : FALSE;
  }

  /**
   * Load List of Meta tags.
   *
   * @param string $getActiveId
   *   The domain id.
   *
   * @return []
   *   An array of the meta tags.
   */
  public function domainSiteVerifyListsMetaTags($getActiveId) {
    $metaTagsLists = $this->database->select('md_site_verify', 'mdsv')
      ->fields('mdsv', ['dsv_id', 'meta'])
      ->condition('meta', '', '<>')
      ->condition('domain_id', $getActiveId)
      ->execute()
      ->fetchAllKeyed();
    return $metaTagsLists;
  }

  /**
   * Menu load callback; loads engine details.
   *
   * @param string $engine
   *   A string with the engine shortname.
   *
   * @return array
   *   An arary of the engine details, or FALSE if not found.
   */
  public function domainSiteVerifyEngineLoad($engine) {
    $engines = $this->domainSiteVerifyGetEngines();
    return isset($engines[$engine]) ? $engines[$engine] : FALSE;
  }

  /**
   * Fetch an array of supported search engines.
   */
  public function domainSiteVerifyGetEngines() {
    static $engines;

    if (!isset($engines)) {
      // Fetch the list of engines and allow other modules to alter it.
      $engines = $this->moduleHandler->invokeAll('md_site_verify_engine_info');

      $this->moduleHandler->alter('md_site_verify_engine', $engines);
      // Merge the default values for each engine entry.
      foreach ($engines as $key => $engine) {
        $engines[$key] += [
          'key' => $key,
          'name' => Unicode::ucfirst($engine['name']),
          'file' => FALSE,
          'file_example' => FALSE,
          'file_validate' => [],
          'file_contents' => FALSE,
          'file_contents_example' => FALSE,
          'file_contents_validate' => [],
          'meta' => FALSE,
          'meta_example' => FALSE,
          'meta_validate' => [],
        ];
      }
    }

    return $engines;
  }

}
