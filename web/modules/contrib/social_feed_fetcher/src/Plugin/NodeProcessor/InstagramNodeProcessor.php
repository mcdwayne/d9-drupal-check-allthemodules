<?php

namespace Drupal\social_feed_fetcher\Plugin\NodeProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\social_feed_fetcher\PluginNodeProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InstagramNodeProcessor
 * @package Drupal\social_feed_fetcher\Plugin\NodeProcessor
 *
 * @PluginNodeProcessor(
 *   id = "instagram_processor",
 *   label = @Translation("Instagram node processor")
 * )
 */
class InstagramNodeProcessor extends PluginNodeProcessorPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($source, $data_item) {
    if (!$this->isPostIdExist($data_item['raw']->id)) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $time */
      $time = new DrupalDateTime();
      $time->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
      $time->setTimestamp($data_item['raw']->created_time);
      $string = $time->format(DATETIME_DATETIME_STORAGE_FORMAT);
      $node = $this->entityStorage->create([
        'type' => 'social_post',
        'title' => 'Post ID: ' . $data_item['raw']->id,
        'field_platform' => ucwords($source),
        'field_id' => $data_item['raw']->id,
        'field_post' => [
          'value' => social_feed_fetcher_linkify(html_entity_decode($data_item['raw']->caption->text)),
          'format' => $this->config->get('formats_post_format'),
        ],
        'field_social_feed_link' => [
          'uri' => $data_item['raw']->link,
          'title' => '',
          'options' => [],
        ],
        'field_sp_image' => [
          'target_id' => $this->processImageFile($data_item['media_url'],'public://instagram'),
        ],
        'field_posted' => [
          'value' => $string
        ],
      ]);
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
    return file_save_data($data, $uri[0], FILE_EXISTS_REPLACE)->id();
  }

}
