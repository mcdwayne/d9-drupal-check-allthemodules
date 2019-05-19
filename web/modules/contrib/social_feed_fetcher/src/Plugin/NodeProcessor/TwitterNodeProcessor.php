<?php

namespace Drupal\social_feed_fetcher\Plugin\NodeProcessor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\social_feed_fetcher\PluginNodeProcessorPluginBase;

/**
 * Class TwitterNodeProcessor
 *
 * @package Drupal\social_feed_fetcher\Plugin\NodeProcessor
 *
 * @PluginNodeProcessor(
 *   id = "twitter_processor",
 *   label = @Translation("Twitter node processor")
 * )
 */
class TwitterNodeProcessor extends PluginNodeProcessorPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($source, $data_item) {
    if (!$this->isPostIdExist($data_item->id)) {
      $tweet = [
        'type' => 'social_post',
        'title' => 'Post ID: ' . $data_item->id,
        'field_platform' => ucwords($source),
        'field_id' => $data_item->id,
        'field_post' => [
          'value' => social_feed_fetcher_linkify(html_entity_decode($data_item->full_text)),
          'format' => $this->config->get('formats_post_format'),
        ],
        'field_posted' => $this->setPostTime($data_item->created_at),
      ];
      if (isset($data_item->entities->media[0]->url)) {
        $tweet['field_social_feed_link'] = [
          'uri' => $data_item->entities->media[0]->url,
          'title' => '',
          'options' => [],
        ];
      }
      if (isset($data_item->entities->media[0]->media_url_https)) {
        $tweet['field_sp_image'] = [
          'target_id' => $this->processImageFile($data_item->entities->media[0]->media_url_https, 'public://twitter'),
        ];
      }
      $node = $this->entityStorage->create($tweet);
      return $node->save();
    }
    return FALSE;
  }

  /**
   * Save external file.
   *
   * @param $filename
   * @param $path
   *
   * @return int
   */
  public function processImageFile($filename, $path) {
    $name = basename($filename);
    $response = $this->httpClient->get($filename);
    $data = $response->getBody();
    $uri = $path . '/' . $name;
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    $uri = explode('?', $uri);
    if (!file_save_data($data, $uri[0], FILE_EXISTS_REPLACE)) {
      return 0;
    }
    return file_save_data($data, $uri[0], FILE_EXISTS_REPLACE)->id();
  }

}
