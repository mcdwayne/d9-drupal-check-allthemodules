<?php
/**
 * @file
 * Contains \Drupal\cincopa\Controller\CincopaGallery.
 */
namespace Drupal\cincopa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CincopaGallery.
 *
 * @package Drupal\cincopa\Controller
 */
class CincopaGallery extends ControllerBase {
  /**
   * {@inheritdoc}
   */
  public function content(Request $request) {
    // Add New Gallery URL
    $url = "https://www.cincopa.com/media-platform/start.aspx";
    return array(
      '#theme' => 'cincopa',
      '#url' => $url
    );
 
  }
}