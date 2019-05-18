<?php

namespace Drupal\get_node_img\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\Config;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller class for serving images from imagefields.
 */
class GetNodeImg extends ControllerBase {

  /**
   * Router callback.
   *
   * @param Drupal\node\Entity\Node $node
   *   Drupal node entity.
   * @param string $field_name
   *   Machine name for an image field; e.g. field_image.
   * @param string $filename
   *   [Optional] This could be an integer or a string.  A string is always
   *   treated as zero.  An image extension can be appended to the integer
   *   as well; e.g. 1.png.
   */
  public function getImage(Node $node, $field_name, $filename) {

    $img_num = (int) $filename;
    $img_uri = '';
    $img_timestamp = 0;

    if (!isset($node->{$field_name}[$img_num]->entity)) {
      // Try to send our default image.
      $img_404_fid = $this->config->get('404_img');

      if ($img_404 = File::load($img_404_fid)) {
        $img_uri = $img_404->getFileUri();
        $img_timestamp = $img_404->getCreatedTime();
      }
      else {
        throw new NotFoundHttpException();
      }
    }
    else {
      // Grab the image from imagefield.
      $img = $node->{$field_name}[$img_num]->entity;
      $img_uri = $img->getFileUri();
      $img_timestamp = $img->getCreatedTime();
    }

    $headers = $this->prepareImgHeaders($img_uri);
    list($http_response_code, $headers) = $this->addCachingHeaders($img_timestamp, $headers);

    return new BinaryFileResponse($img_uri, $http_response_code, $headers);
  }

  /**
   * Prepare HTTP headers for serving the given image URI.
   *
   * @param string $img_uri
   *   Image URI e.g. public://images/foo.png .
   *
   * @return array
   *   HTTP headers for the given image URL.
   */
  protected function prepareImgHeaders($img_uri) {

    $headers = $this->moduleHandler()->invokeAll('file_download', [$img_uri]);

    return $headers;
  }

  /**
   * Add image caching related headers.
   *
   * Also determine the HTTP response code.
   *
   * @param int $timestamp
   *   Unix timestamp for last modification of image.
   * @param array $headers
   *   HTTP header array.
   *
   * @return array
   *   First item is HTTP response code, second item is updated HTTP header
   *   array.
   */
  public function addCachingHeaders($timestamp, array $headers) {

    // Set default values:
    $last_modified = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    $etag = '"' . md5($last_modified) . '"';
    $http_response_code = 200;

    // See if the client has provided the required HTTP headers:
    $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                         ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                         : FALSE;
    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH'])
                     ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])
                     : FALSE;

    if ($if_modified_since and $if_none_match
        and $if_none_match == $etag
        and $if_modified_since == $last_modified) {
      // if-modified-since must match.
      $http_response_code = 304;

      // All 304 responses must send an etag if the 200 response
      // for the same object contained an etag.
      $header['Etag'] = $etag;

      // We must also set Last-Modified again, so that we overwrite Drupal's
      // default Last-Modified header with the right one.
      $headers['Last-Modified'] = $last_modified;

      return [$http_response_code, $headers];
    }

    // Send appropriate response:
    $headers['Last-Modified'] = $last_modified;
    $headers['ETag']          = $etag;

    return [$http_response_code, $headers];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Config $config) {

    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    $config = $container->get('config.factory')->get('get_node_img.settings');
    return new static($config);
  }

  /**
   * Configuration object for module settings.
   *
   * @var Drupal\Core\Config\Config
   */
  protected $config;

}
