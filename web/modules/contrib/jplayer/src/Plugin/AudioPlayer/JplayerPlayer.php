<?php

/**
 * @file
 * Contains \Drupal\audiofield\Plugin\AudioPlayer\DefaultMp3Player.
 */

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\audiofield\AudioFieldPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\FileInterface;

/**
 * @AudioPlayer (
 *   id = "jplayer_player",
 *   title = @Translation("jPlayer mp3 player"),
 *   file_types = {
 *     "mp3",
 *   },
 *   description = "jPlayer audio player."
 * )
 */
class JplayerPlayer implements AudioFieldPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function renderPlayer(FileInterface $file) {
    $file_uri = $file->getFileUri();
    $url = Url::fromUri(file_create_url($file_uri));
    $markup = "<p>I am a jplayer audio player!</p><audio controls>
                   <source src='" . $url->toString() . "' type='audio/mpeg'>
                   Your browser does not support the audio element.
                   </audio>";
    return ['#markup' => Markup::create($markup)];
  }
}