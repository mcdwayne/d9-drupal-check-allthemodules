<?php

/**
 * @file
 * Contains \Drupal\youtube_formatter\Plugin\field\formatter\YoutubeFormatterVideo.
 */

namespace Drupal\youtube_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\youtube_formatter\YoutubeFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'youtube formatter video' formatter.
 *
 * @FieldFormatter(
 *   id = "youtube_formatter_video",
 *   label = @Translation("Youtube formatter video"),
 *   field_types = {
 *     "text",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class YoutubeFormatterVideo extends YoutubeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $video_uri = $this->getYoutubeUri($item, $delta);
      $elements[$delta] = [
        '#markup' => '<iframe width="' . $this->getSetting('width'). '" height="' . $this->getSetting('height') . '" src="' . $video_uri . '" frameborder="0" allowfullscreen></iframe>',
      ];
    }

    return $elements;
  }


}
