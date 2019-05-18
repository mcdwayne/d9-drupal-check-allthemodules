<?php

namespace Drupal\audio_embed_field\Plugin\audio_embed_field\Provider;

use Drupal\audio_embed_field\ProviderPluginBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * A SoundCloud provider plugin.
 *
 * @AudioEmbedProvider(
 *   id = "soundcloud",
 *   title = @Translation("SoundCloud")
 * )
 */
class SoundCloud extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    if ($autoplay == 0) {
      $autoplay = 'false';
    }
    if ($autoplay == 1) {
      $autoplay = 'true';
    }
    $embed_code = [
      '#type' => 'audio_embed_iframe',
      '#provider' => 'soundcloud',

      '#url' => sprintf('https://w.soundcloud.com/player/?url=https%%3A//api.soundcloud.com/tracks/%s', $this->getAudioId()),
      '#query' => [
        'auto_play' => $autoplay,
        'visual' => 'true',
        'show_user' => 'false',
        'show_reposts' => 'false',
        'hide_related' => 'true',
        'show_comments' => 'false',
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
      ],
    ];

    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {

    try {
      $client = new Client();
      $res = $client->request('GET', 'https://api.soundcloud.com/resolve.json', [
        'query' => [
          'url' => $this->getInput(),
          'client_id' => \Drupal::Config('audio_embed_field.settings')->get('soundcloud_id'),
        ],
      ]);

      return json_decode($res->getBody())->artwork_url;

    }
    catch (ClientException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {

    try {
      $client = new Client();
      $res = $client->request('GET', 'https://api.soundcloud.com/resolve.json', [
        'query' => [
          'url' => $input,
          'client_id' => \Drupal::Config('audio_embed_field.settings')->get('soundcloud_id'),
        ],
      ]);

      return json_decode($res->getBody())->id;

    }
    catch (ClientException $e) {
      return NULL;
    }

  }

}
