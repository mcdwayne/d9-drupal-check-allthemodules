<?php
/**
 * @file
 * Plugin: jsDelivr.
 */

namespace Drupal\libraries_cdn\Plugin\LibrariesCdn;

use Drupal\libraries_cdn\Annotation\LibrariesCdn;
use Drupal\libraries_cdn\CdnBase;
use Drupal\libraries_cdn\CdnBaseInterface;

/**
 * Class JsDelivr.
 *
 * @LibrariesCdn(
 *  id = "jsdelivr",
 *  description = "jsDelivr Integration"
 * )
 */
class JSDelivr extends CdnBase implements CdnBaseInterface {
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (empty($configuration['urls'])) {
      $configuration['urls'] = array();
    }
    $configuration['urls'] += array(
      'isAvailable' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries/%s',
      'getInformation' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=name,mainfile,lastversion,description,homepage,github,author',
      'getVersions' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=versions',
      'getFiles' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=%s&fields=assets',
      'search' => 'http://api.jsdelivr.com/v1/jsdelivr/libraries?name=*%s*',
      'convertFiles' => '//cdn.jsdelivr.net/%s/%s/',
    );

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function formatData($function, array $data = array()) {
    switch ($function) {
      case 'getVersions':
        return isset($data[0]) && isset($data[0]['versions']) ? $data[0]['versions'] : array();

      case 'getFiles':
        return isset($data[0]) && isset($data[0]['assets']) ? $data[0]['assets'] : array();

      case 'getLatestVersion':
        return isset($data['lastversion']) ? $data['lastversion'] : NULL;

      case 'getInformation':
        return isset($data[0]) ? $data[0] : array();

      default:
        return $data;
    }
  }

}
