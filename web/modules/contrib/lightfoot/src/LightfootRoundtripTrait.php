<?php

namespace Drupal\lightfoot;

use Drupal\Core\PrivateKey;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Drupal\Core\State\StateInterface;

/**
 * Provides methods for an optimizer to manage serialization, signing,
 * filenames, and paths.
 */
trait LightfootRoundtripTrait {
  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  protected static function encode($decoded) {
    $json = json_encode($decoded);
    $dh = deflate_init(ZLIB_ENCODING_DEFLATE);
    $deflated = deflate_add($dh, $json, ZLIB_FINISH);
    $encoded = base64_encode($deflated);
    $swapped = str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
    return $swapped;
  }

  protected static function decode($encoded) {
    $unswapped = str_replace(['-', '_', ''], ['+', '/', '='], $encoded);
    $deflated = base64_decode($unswapped);
    $dh = inflate_init(ZLIB_ENCODING_DEFLATE);
    $inflated = inflate_add($dh, $deflated, ZLIB_FINISH);
    $decoded = json_decode($inflated, TRUE);
    return $decoded;
  }

  /**
   * Generate a signed filename for a given group of asset paths.
   *
   * @param array $paths
   *   Paths to assets for this group.
   * @param string $extension
   *   The file extension to use with the generated path: "js" or "css"
   *
   * @return string
   *   A path to fetch the given group of assets.
   */
  protected static function generateFilename(array $css_or_js_group, $extension, PrivateKey $key) {
    //print_r($css_or_js_group);

    $css_or_js_paths = array();
    foreach ($css_or_js_group['items'] as $css_or_js_item) {
      $css_or_js_paths[] = $css_or_js_item['data'];
    }

    //print_r($css_or_js_paths);

    // Generate the compressed key.
    $encoded = self::encode($css_or_js_paths);

    // Add a signature.
    $signature = Crypt::hmacBase64($encoded, $key->get());

    return $encoded .'.'. $signature .'.' . $extension;
  }

  /**
   * Generate a signed URI with a query string (for cache busting) for the
   * specified assets.
   *
   * @param array $paths
   *   Paths to assets for this group.
   * @param string $extension
   *   The file extension to use with the generated path: "js" or "css"
   *
   * @return string
   *   A signed URI to fetch the given group of assets.
   */
  protected static function generateUri(array $css_or_js_items, $extension, PrivateKey $key, StateInterface $state) {
    $filename = self::generateFilename($css_or_js_items, $extension, $key);
    $query_string = $state->get('system.css_js_query_string') ?: '0';

    // @TODO: Don't bother with query string for CSS. It's added by Drupal later anyway.
    $uri = Url::fromRoute('lightfoot.delivery', array('filename' => $filename), array('query' => array('v' => $query_string)));
    //$uri = '/~straussd/lightfoot-d8/lightfoot/deliver/'. $filename .'?v='. $query_string;
    return $uri;
  }

  /**
   * Validate and parse a filename created as part of self::generatePath().
   *
   * @param string $filename
   *   Filename to validate and parse
   *
   * @return array|boolean
   *   An array of file paths to return aggregated or FALSE (on failure).
   */
  public static function validateAndParseFilename($filename, PrivateKey $key) {
    $parts = explode('.', $filename);

    $encoded = $parts[0];
    $signature = $parts[1];

    // Validate the signature.
    $expected_signature = Crypt::hmacBase64($encoded, $key->get());

    if ($expected_signature !== $signature) {
      // Callers should probably throw AccessDeniedHttpException on failures.
      return FALSE;
    }

    $decoded = self::decode($encoded);
    return $decoded;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    // We don't track aggregates statefully, so we can only return empty data.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    // We don't write any files, so there is nothing to delete.
  }

}
