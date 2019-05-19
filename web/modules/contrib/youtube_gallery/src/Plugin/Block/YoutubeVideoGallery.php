<?php

namespace Drupal\youtube_gallery\Plugin\Block;

/**
 * @file
 * Create a custom block for rendering youtube videos.
 */

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\youtube_gallery\Controller\youtubeConfig;

/**
 * Provides a 'youtube gallery' block.
 *
 * @Block(
 *   id = "youtube_gallery_block",
 *   admin_label = @Translation("Youtube Gallery"),
 *   category = @Translation("Youtube Channel Videos ")
 * )
 */
class YoutubeVideoGallery extends BlockBase implements ContainerFactoryPluginInterface {

  protected $youtube;

  /**
   * Implemets construct for create depenndency injection.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        youtubeConfig $youtubeConfig
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->youtube = $youtubeConfig;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('youtube_gallery.content')
    );
  }

  /**
   * Build a youtube gallery block.
   */
  public function build() {

    global $base_url;

    $content = $this->youtube->getYoutubeVideos();

    if ($content !== NULL) {

      $total = $this->youtube->getMaxVideos();

      $data = [];

      for ($i = 0; $i < $total; $i++) {

        $videoId    = $content['items'][$i]['snippet']['resourceId']['videoId'];
        $videoTitle = $content['items'][$i]['snippet']['title'];
        $thumbnail  = $content['items'][$i]['snippet']['thumbnails']['medium']['url'];
        $duration   = $this->youtube->getVideoDuration($videoId);

        $data[$i]['video_id'] = $videoId;
        $data[$i]['video_title'] = $videoTitle;
        $data[$i]['thumbnail'] = $thumbnail;
        $data[$i]['duration'] = $duration;

        $link = $base_url . "/youtube-gallery/" . $videoId;

        $data[$i]['url'] = $link;

      }

      return [
        '#theme' => 'youtube_gallery_block',
        '#youtube_content' => $data,
      ];
    }
    else {

      return [
        '#type' => 'markup',
        '#markup' => '<h3>Videos rendering faild...!!!</h3>',
      ];
    }

  }

}
