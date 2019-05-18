<?php

namespace Drupal\getresponse\Service;

use \Drupal\Component\Utility\Html;

/**
 * Class dealing with GetResponse rss
 *
 * @file
 * Rss Class.
 */
class Rss {

  /**
   * Functions parse XML data, return string|FALSE.
   *
   * @param $element_name
   * @param $xml
   * @param bool $content_only
   * @param bool $match_all
   * @return bool
   */
  protected function parseXMLdata(
    $element_name,
    $xml,
    $content_only = TRUE,
    $match_all = FALSE
  ) {
    if ($xml == FALSE) {
      return FALSE;
    }

    if ($match_all) {
      $found = preg_match_all(
        '#<' . preg_quote($element_name) . '(?:\s+[^>]+)?>' . '(.*?)</' . preg_quote($element_name) . '>#s',
        $xml,
        $matches,
        PREG_PATTERN_ORDER
      );
    }
    else {
      $found = preg_match(
        '#<' . preg_quote($element_name) . '(?:\s+[^>]+)?>(.*?)' . '</' . preg_quote($element_name) . '>#s',
        $xml,
        $matches
      );
    }

    if ($found != FALSE) {
      return ($content_only) ? $matches[1] : $matches[0];
    }

    return FALSE;
  }

  /**
   * Function return RSS data as html string.
   */
  public function renderRss($limit = 5) {
    try {
      /** @var \GuzzleHttp\Client $client */
      $client = \Drupal::httpClient();
      $request = $client->get('http://blog.getresponse.com/feed');
      $result = $request->getBody();
    } catch (\Exception $e) {
      $result = '';
    }

    if (!empty($result)) {
      $news_items = $this->parseXMLdata('item', $result, FALSE, TRUE);
      $item_array = array();

      if (is_array($news_items)) {
        foreach ($news_items as $item) {
          $title = $this->parseXMLdata('title', $item);
          $url = $this->parseXMLdata('link', $item);
          $item_array[] = array(
            'title' => $title,
            'url' => $url,
          );
        }
        $html = '<ul class="GR_rss_ul">';
        $count = 0;
        if (count($news_items) > 0) {
          foreach ($item_array as $item) {
            $html .= '<li class="GR_rss_li">';
            $html .= '<a href="' . Html::escape($item['url']) . '" target="_blank">' . $item['title'] . '</a>';
            $html .= '</li>';
            if (++$count == $limit) {
              break;
            }
          }
          $html .= '<ul>';
        }
      }
      else {
        $html = \Drupal::translation()->translate("No RSS found.");
      }
    }
    else {
      $html = \Drupal::translation()->translate("Unable to find RSS feed");
    }
    return $html;
  }

}
