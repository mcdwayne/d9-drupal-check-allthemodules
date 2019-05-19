<?php

namespace Drupal\youtube_gallery\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\CurrentPathStack;
/**
 * @file
 * Call module template and play the video.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Creating Class for return custom page template.
 */
class PlayVideo extends ControllerBase {

  protected $upload;
  protected $currentPath;

  /**
   * Implemets construct for create depenndency injection.
   */
  public function __construct(youtubeConfig $configuration, CurrentPathStack $currentpath) {

    $this->upload = $configuration;
    $this->currentPath = $currentpath;
  }

  /**
   * Create dependency injection for class.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('youtube_gallery.content'),
      $container->get('path.current')
    );

  }

  /**
   * Return custom theme and variables.
   */
  public function content($vid) {

    $content = $this->upload->getYoutubeVideos();

    $getVideoId = explode('/', trim($this->currentPath->getPath(), '/'));

    $currentVideo = end($getVideoId);

    $getCurrrentVideo = $this->upload->getCurrentVideo($currentVideo);

    return [
      '#theme' => 'youtube_gallery',
      '#content' => $content,
      '#currentVideo' => $getCurrrentVideo,
    ];

  }

}
