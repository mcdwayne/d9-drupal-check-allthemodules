<?php

namespace Drupal\youtube_gallery\Controller;

/**
 * @file
 * Creates base controller.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get the youtube status and print to page.
 */
class YoutubeGallery extends ControllerBase {

  protected $youtube;

  /**
   * Implemets the constuct for create class object.
   */
  public function __construct(youtubeConfig $configuration) {

    $this->youtube = $configuration;
  }

  /**
   * Create dependency injection for class.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('youtube_gallery.content')
    );

  }

  /**
   * Get API status.
   */
  public function youtubeStatus() {

    $apikey        = $this->youtube->getApiKey();
    $channelId     = $this->youtube->getChannelId();
    $maxvideos     = $this->youtube->getMaxVideos();
    $totalvideos   = $this->youtube->getTotalVideos();
    $channelTitle  = $this->youtube->getChannelTitle();
    $youtubeVideos = $this->youtube->getYoutubeVideos();

    if (isset($youtubeVideos) == NULL) {

      $apikey = "None";
      $channelId = "None";
      $maxvideos = "None";
      $channelTitle = "None";
      $totalvideos = "None";

    }

    $output = '<p><strong>Google API key : </strong> ' . $apikey . '</p>';
    $output .= '<p><strong>Youtube Channel ID : </strong> ' . $channelId . '</p>';
    $output .= '<p><strong>Total Videos : </strong>' . $totalvideos . '</p>';
    $output .= '<p><strong>No. of videos display : </strong> ' . $maxvideos . '</p>';
    $output .= '<p><strong>Channel Name : </strong> ' . $channelTitle . '</p>';

    $content = [
      '#type'    => 'markup',
      '#markup'    => $output,
    ];
    return $content;
  }

}
