<?php

namespace Drupal\social_feed_fetcher\Plugin\NodeProcessor;

use Drupal\node\Entity\Node;
use Drupal\social_feed_fetcher\PluginNodeProcessorPluginBase;

/**
 * Class FacebookNodeProcessor
 *
 * @package Drupal\social_feed_fetcher\Plugin\NodeProcessor
 *
 * @PluginNodeProcessor(
 *   id = "facebook_processor",
 *   label = @Translation("Facebook node processor")
 * )
 */
class FacebookNodeProcessor extends PluginNodeProcessorPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($source, $data_item) {
    if (!$this->isPostIdExist($data_item['id'])) {
      $fbpost = [
        'type' => 'social_post',
        'title' => 'Post ID: ' . $data_item['id'],
        'field_platform' => ucwords($source),
        'field_id' => $data_item['id'],
        'field_post' => [
          'value' => social_feed_fetcher_linkify(html_entity_decode($data_item['message'] ?: '')),
          'format' => $this->config->get('formats_post_format'),
        ],
        'field_posted' => [
          'value' => $this->setPostTime($data_item['created_time']),
        ],
      ];
      if (isset($data_item['link'])) {
        $fbpost['field_social_feed_link'] = [
          'uri' => $data_item['link'],
          'title' => '',
          'options' => [],
        ];
      }      
      if (isset($data_item['image'])) {
        $fbpost['field_sp_image'] = [
          'target_id' => $this->processImageFile($data_item['image'], 'public://facebook')
        ];
      }
      $node = $this->entityStorage->create($fbpost);
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
    $response = $this->httpClient->get($filename);
    $data = $response->getBody();
    $uri = $path . '/' . mt_rand() . '.jpg';
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    return file_save_data($data, $uri, FILE_EXISTS_REPLACE)->id();
  }

}
