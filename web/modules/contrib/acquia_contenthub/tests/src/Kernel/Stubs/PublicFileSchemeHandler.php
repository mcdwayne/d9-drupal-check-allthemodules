<?php

namespace Drupal\Tests\acquia_contenthub\Kernel\Stubs;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\Plugin\FileSchemeHandler\PublicFileSchemeHandler as OriginalPublicFileSchemeHandler;

/**
 * Class PublicFileSchemeHandler.
 *
 * @group acquia_contenthub
 *
 * @package Drupal\Tests\acquia_contenthub\Kernel\Stubs
 */
class PublicFileSchemeHandler extends OriginalPublicFileSchemeHandler {

  /**
   * {@inheritdoc}
   */
  public function getFile(CDFObject $object) {
    if ($object->getAttribute('file_location') && $object->getAttribute('file_uri')) {
      $url = $object->getAttribute('file_location')->getValue()['und'];

      $url = str_replace('module::', drupal_get_path('module', 'acquia_contenthub'), $url);

      $contents = file_get_contents($url);
      $uri = $object->getAttribute('file_uri')->getValue()['und'];
      return file_unmanaged_save_data($contents, $uri, FILE_EXISTS_REPLACE);
    }
    return FALSE;
  }

}
