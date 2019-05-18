<?php

namespace Drupal\feed_block\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Plugin implementation of the 'rss_feed_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "rss_feed_formatter",
 *   label = @Translation("RSS Feed Formatter"),
 *   field_types = {
 *     "rss_feed_field"
 *   }
 * )
 */
class RSSFeedFormatter extends FormatterBase {

  use StringTranslationTrait;

  /**
   * Guzzle request.
   */
  public function performRequest($siteUrl) {
    $client = new Client();
    try {
      $res = $client->get($siteUrl, ['http_errors' => FALSE]);
      return($res->getBody(TRUE)->getContents());
    }
    catch (RequestException $e) {
      return($this->t('Error'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item->feed_uri) {
        $responseXml = simplexml_load_string($this->performRequest($item->feed_uri));
        if (isset($responseXml->item)) {
          $feed_object = $responseXml->item;
        }
        elseif (isset($responseXml->entry)) {
          // Youtube format.
          $feed_object = $responseXml->entry;
        }
        else {
          $feed_object = $responseXml->channel->item;
        }
        $feed = [];
        $inc = 0;
        while ($inc < $item->count) {
          $instance = $feed_object[$inc];
          if (!isset($instance->link)) {
            break;
          }
          $elements[$delta][$inc] = array(
            '#theme' => 'feed_block_rss_item',
            '#title' => (string) $instance->title,
          );
          if (isset($instance->link->attributes()->href)) {
            // Youtube format.
            $elements[$delta][$inc]['#url'] = (string) $instance->link->attributes()->href;
          }
          elseif (isset($instance->link)) {
            $elements[$delta][$inc]['#url'] = (string) $instance->link;
          }
          if ($item->display_date) {
            unset($date);
            if (isset($instance->pubDate)) {
              $date = strtotime($instance->pubDate);
            }
            elseif (isset($instance->published)) {
              // Youtube format.
              $date = strtotime($instance->published);
            }
            if (!empty($date)) {
              $elements[$delta][$inc]['#date'] = \Drupal::service('date.formatter')->format($date, $item->date_format, $item->custom_date_format);
            }
          }
          if ($item->display_description) {
            $description = (string) $instance->description;
            if ($item->description_plaintext) {
              $description = strip_tags($description);
            }
            if ($item->description_length != 0) {
              $description = Unicode::truncate($description, $item->description_length, TRUE, TRUE);
            }
            $elements[$delta][$inc]['#description'] = $description;
          }
          $inc++;
        }
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#cache']['tags'][] = 'feed_block';
    return $elements;
  }

}
