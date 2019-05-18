<?php

namespace Drupal\cocoon_media\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\cocoon_media\CocoonController;

/**
 * Class CMMController.
 *
 * @package Drupal\cocoon_media\Controller
 */
class CMMController extends ControllerBase {

  /**
   * Cocoon_media configuration settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * CocoonController.
   *
   * @var \Drupal\cocoon_media\CocoonController
   */
  protected $cocoonController;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->settings = $this->config('cocoon_media.settings');
    $this->cocoonController = new CocoonController(
      $this->settings->get('cocoon_media.domain'),
      $this->settings->get('cocoon_media.username'),
      $this->settings->get('cocoon_media.api_key'));
  }

  /**
   * Get tags using the cocoonController.
   *
   * @param \Symfony\Component\HttpFoundation\Request $req
   *   Request value.
   * @param string $tag_name
   *   The name of the tag.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response from cocoon in json.
   */
  public function getTagsAutocomplete(Request $req, $tag_name = '') {
    $params = $req->query->get('q');
    $tags_list = get_cached_data('cocoon_media:all_tags', [$this->cocoonController, 'getTags']);
    $tagNames = [];
    // Using autocomplete in forms does not work properly with paths,
    // I am adding this 'trick':
    // If tag_name is empty but parameter is not then us parameter.
    $tag_name = $tag_name ? $tag_name : $params;
    foreach ($tags_list as $tag) {
      if ($tag['used'] > 0) {
        $string_found = $tag_name ? strpos($tag['name'], $tag_name) : TRUE;
        if ($string_found !== FALSE) {

          $tagNames[] = [
            'value' => $tag['name'],
            'label' => $tag['name'],
          ];
        }
      }
    }
    return new JsonResponse($tagNames);
  }

}
