<?php

namespace Drupal\sticky_sharrre_bar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for Sticky Sharrre Bar route.
 *
 * @package Drupal\sticky_sharrre_bar\Controller
 */
class StickySharrreBarController extends ControllerBase {

  /**
   * Emulation functional of php file from http://sharrre.com.
   *
   * @return array
   *   A render array representing the administrative page content.
   */
  public function sharrre() {
    $url = $_GET['url'];
    $type = $_GET['type'];
    $output = ['url' => $url, 'count' => 0];

    // We shouldn't cache the router, to avoid the same result for all pages,
    // like {"url": null, "count": 0}
    \Drupal::service('page_cache_kill_switch')->trigger();

    if (filter_var($url, FILTER_VALIDATE_URL)) {
      if ($type == 'googlePlus') {
        $contents = $this->stickySharrreBarParse('https://plusone.google.com/u/0/_/+1/fastbutton?url=' . $url . '&count=true');
        preg_match('/window\.__SSR = {c: ([\d]+)/', $contents, $matches);
        if (isset($matches[0])) {
          $output['count'] = (int) str_replace('window.__SSR = {c: ', '', $matches[0]);
        }
      }
      else {
        if ($type == 'stumbleupon') {
          $content = $this->stickySharrreBarParse('http://www.stumbleupon.com/services/1.01/badge.getinfo?url=' . $url);
          $result = Json::decode($content);
          if (isset($result->result->views)) {
            $output['count'] = Html::escape($result->result->views);
          }
        }
      }
    }

    return new JsonResponse($output);
  }

  /**
   * Get necessary content use cURL.
   *
   * @param string $encoded_url
   *   Url of page.
   *
   * @return \Psr\Http\Message\StreamInterface
   *   Ready data
   */
  private function stickySharrreBarParse($encoded_url) {
    $client = \Drupal::httpClient();
    $request = $client->get($encoded_url);

    return $request->getBody();
  }

}
