<?php
/**
 * @file
 * Contains Drupal\block_render\Response\AssetResponse.
 */

namespace Drupal\block_render\Response;

use Drupal\block_render\Immutable;
use Drupal\block_render\Libraries\LibrariesInterface;

/**
 * The asset response data.
 */
final class AssetResponse extends Immutable implements AssetResponseInterface {

  /**
   * Libraries.
   *
   * @var \Drupal\block_render\Data\LibraryResponseInterface
   */
  protected $libraries;

  /**
   * Header Assets.
   *
   * @var array
   */
  protected $header;

  /**
   * Footer Assets.
   *
   * @var array
   */
  protected $footer;

  /**
   * Create the Asset Response object.
   *
   * @param \Drupal\block_render\Libraries\LibrariesInterface $libraries
   *   A library response object.
   * @param array $header
   *   Header Assets.
   * @param array $footer
   *   Footer Assets.
   */
  public function __construct(LibrariesInterface $libraries, array $header, array $footer) {
    $this->libraries = $libraries;
    $this->header = $header;
    $this->footer = $footer;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * {@inheritdoc}
   */
  public function getFooter() {
    return $this->footer;
  }

}
