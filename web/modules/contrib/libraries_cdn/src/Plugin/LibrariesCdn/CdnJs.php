<?php
/**
 * @file
 * Plugin: CDNJS.
 */

namespace Drupal\libraries_cdn\Plugin\LibrariesCdn;

use Drupal\libraries_cdn\Annotation\LibrariesCdn;
use Drupal\libraries_cdn\CdnBase;
use Drupal\libraries_cdn\CdnBaseInterface;

/**
 * Class CdnJs.
 *
 * @LibrariesCdn(
 *  id = "cdnjs",
 *  description = "CDNJS Integration"
 * )
 */
class CdnJs extends CdnBase implements CdnBaseInterface {
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (empty($configuration['urls'])) {
      $configuration['urls'] = array();
    }
    $configuration['urls'] += array(
      'isAvailable' => 'http://api.cdnjs.com/libraries?search=%s',
      'getInformation' => 'http://api.cdnjs.com/libraries/%s',
      'getVersions' => 'http://api.cdnjs.com/libraries/%s',
      'getFiles' => 'http://api.cdnjs.com/libraries/%s',
      'search' => 'http://api.cdnjs.com/libraries?search=%s',
      'convertFiles' => '//cdnjs.cloudflare.com/ajax/libs/%s/%s/',
    );

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function formatData($function, array $data = array()) {
    switch ($function) {
      case 'search':
      case 'isAvailable':
        return isset($data['results']) ? (array) $data['results'] : $data;

      case 'getVersions':
      case 'getFiles':
        return isset($data['assets']) ? (array) $data['assets'] : $data;

      case 'getLatestVersion':
        return isset($data['version']) ? $data['version'] : NULL;

      default:
        return $data;
    }
  }

}
