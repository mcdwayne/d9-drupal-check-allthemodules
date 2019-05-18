<?php

namespace Drupal\s3fs_file_proxy_to_s3\PathProcessor;

use Drupal\s3fs\PathProcessor\S3fsPathProcessorImageStyles;

/**
 * Defines a path processor to rewrite image styles URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 *
 * This processor handles Amazon S3 public image style callback:
 * - In order to allow the webserver to serve these files with dynamic args
 *   the route is registered under /s3/files/styles prefix and change internally
 *   to pass validation and move the file to query parameter. This file will be
 *   processed in S3fsImageStyleDownloadController::deliver().
 *
 * Private files use the normal private file workflow.
 *
 * @see \Drupal\s3fs\Controller\S3fsImageStyleDownloadController::deliver()
 * @see \Drupal\image\Controller\ImageStyleDownloadController::deliver()
 * @see \Drupal\image\PathProcessor\PathProcessorImageStyles::processInbound()
 */
class S3fsFileProxyToS3PathProcessorImageStyles extends S3fsPathProcessorImageStyles {

  const IMAGE_STYLE_PATH_PREFIX = '/s3fs_to_s3/files/styles/';

}
