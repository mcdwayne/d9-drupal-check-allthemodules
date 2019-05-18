<?php

namespace Drupal\mobile_app_links\Controller;

use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\mobile_app_links\Form\AndroidConfigForm;
use Drupal\mobile_app_links\Form\IosConfigForm;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;

/**
 * Customer controller to .well-known links.
 */
class WellKnownController extends ControllerBase {

  /**
   * Page callback for .well-known/assetlinks.json.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON Response.
   */
  public function assetLinks() {
    $config = $this->config(AndroidConfigForm::CONFIG_NAME);

    // Handle caching.
    $cacheMeta = new CacheableMetadata();
    $cacheMeta->addCacheTags($config->getCacheTags());

    $package_name = $config->get('package_name');
    if (empty($package_name)) {
      throw new CacheableNotFoundHttpException($cacheMeta);
    }

    $body = [
      'relation' => [
        'delegate_permission/common.handle_all_urls',
      ],
      'target' => [
        'namespace' => 'android_app',
        'package_name' => $package_name,
        'sha256_cert_fingerprints' => explode(PHP_EOL, $config->get('sha256_cert_fingerprints')),
      ],
    ];

    $response = new CacheableJsonResponse(json_encode($body, JSON_PRETTY_PRINT), 200, [], TRUE);
    $response->addCacheableDependency($cacheMeta);
    return $response;
  }

  /**
   * Page callback for .well-known/apple-app-site-association.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON Response.
   */
  public function appleAppSiteAssociation() {
    $config = $this->config(IosConfigForm::CONFIG_NAME);

    // Handle caching.
    $cacheMeta = new CacheableMetadata();
    $cacheMeta->addCacheTags($config->getCacheTags());

    $appID = $config->get('appID');
    if (empty($appID)) {
      throw new CacheableNotFoundHttpException($cacheMeta);
    }

    $body = [
      'applinks' => [
        'apps' => [],
        'details' => [
          [
            'appID' => $appID,
            'paths' => explode(PHP_EOL, $config->get('paths')),
          ],
        ],
      ],
    ];

    $response = new CacheableJsonResponse(json_encode($body, JSON_PRETTY_PRINT), 200, [], TRUE);
    $response->addCacheableDependency($cacheMeta);
    return $response;
  }

}
