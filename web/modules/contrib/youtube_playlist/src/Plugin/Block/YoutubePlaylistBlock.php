<?php

namespace Drupal\youtube_playlist\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Example: configurable text string' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "youtube_playlist",
 *   admin_label = @Translation("Youtube playlist"),
 *   module = "youtube_playlist"
 * )
 */
class YoutubePlaylistBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * This method sets the block default configuration. This configuration
   * determines the block's behavior when a block is initially placed in a
   * region. Default values for the block configuration form should be added to
   * the configuration array. System default configurations are assembled in
   * BlockBase::__construct() e.g. cache setting and block title visibility.
   *
   * @see \Drupal\block\BlockBase::__construct()
   */
  public function defaultConfiguration() {
    return [
      'playlist_id' =>  '',
      'api_key' => '',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * This method defines form elements for custom block configuration. Standard
   * block configuration fields are added by BlockBase::buildConfigurationForm()
   * (block title and title visibility) and BlockFormController::form() (block
   * visibility settings).
   *
   * @see \Drupal\block\BlockBase::buildConfigurationForm()
   * @see \Drupal\block\BlockFormController::form()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['playlist_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Playlist id'),
      '#default_value' => $this->configuration['playlist_id'],
      '#required' => TRUE,
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Youtube data api key'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockValidate() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['playlist_id']  = $form_state->getValue('playlist_id');
    $this->configuration['api_key']  = $form_state->getValue('api_key');
  }

  public function buildApiRequest($method, $uri, $params) {
    $api_key = $this->configuration['api_key'];
    $default_params = ['key' => $api_key];
    $final_params = array_merge($default_params, $params);
    $client = \Drupal::httpClient();
    $resp = $client->request($method, 'https://www.googleapis.com'.$uri, ['query' => $final_params]);
    return [\json_decode($resp->getBody(), true), $resp->getStatusCode()];
  }
  public function YTDurationToSeconds($duration) {
    preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $match);
    $match = array_slice($match, 1);
    $parts = [];
    foreach ($match as $idx => $x) {
      if($x) {
        $match[$idx] = str_replace('D', '', $x);
      }
    }
      $hours = (int)@$match[0]?(int)$match[0]:0;
      $minutes = (int)@$match[1]?(int)$match[1]:0;
      $seconds = (int)@$match[2]?(int)$match[2]:0;
      $time = [];
      if($hours > 0) {
        array_push($time, $hours < 10?'0'.$hours:$hours);
      }
      array_push($time, $minutes < 10?'0'.$minutes:$minutes);
      array_push($time,$seconds < 10?'0'.$seconds:$seconds);
      $time = implode(':', $time);
      return $time;
    }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if(!$this->configuration['api_key']) {
      return [
        '#theme' => 'youtube_playlist/youtube_playlist',
        '#video' => [],
        '#playlist' => [],
        '#error' => $this->t('Youtube data api key is not defined.'),
      ];
    }
    if(!$this->configuration['playlist_id']) {
      return [
        '#theme' => 'youtube_playlist/youtube_playlist',
        '#video' => [],
        '#playlist' => [],
        '#error' => $this->t('Youtube playlist id is not provided.'),
      ];
    }
    $playlist_id = $this->configuration['playlist_id'];
    $videos = [];
    $first_video = [];
    list($data, $status_code) = $this->buildApiRequest(
      'GET',
      '/youtube/v3/playlistItems',
          [
            'maxResults' =>  '25',
            'part' =>  'snippet,contentDetails',
            'playlistId' =>  'PLO66Zj1D35H2D_2BOzp7AqHzLDuaP8OuN',
          ]);
    if($status_code != 200) {
      return [
        '#theme' => 'youtube_playlist/youtube_playlist',
        '#video' => [],
        '#playlist' => [],
        '#error' => $this->t('Can not get playlist :playlist_id.', [':playlist_id' => $playlist_id]),
      ];
    }
      if($data['pageInfo']['totalResults'] === 0) {
        return [
          '#theme' => 'youtube_playlist/youtube_playlist',
          '#video' => [],
          '#playlist' => [],
          '#error' => $this->t('Playlist is empty.'),
        ];
      }

      foreach ($data['items'] as $i => $v) {
        $video_id = $data['items'][$i]['snippet']['resourceId']['videoId'];
        $title = $data['items'][$i]['snippet']['title'];
        $date = \strtotime($data['items'][$i]['snippet']['publishedAt']);
        $date = date('d.m.Y', $date);
        $thumbnail = $data['items'][$i]['snippet']['thumbnails']['medium'];
        $videos[$video_id] = ['video_id' => $video_id, 'title' => $title,
                              'date' =>  $date, 'thumbnail' => $thumbnail,
                              'duration' => 'none', 'index' =>  $i];
        list($video_info, $status_code) = $this->buildApiRequest('GET', '/youtube/v3/videos', [
          'part' =>  'snippet,contentDetails,statistics',
          'id' =>  $video_id,
        ]);
        if($status_code != 200) {
          return [
            '#theme' => 'youtube_playlist/youtube_playlist',
            '#video' => [],
            '#playlist' => [],
            '#error' => $this->t('Can not get video :video_id.', [':video_id' => $video_id]),
          ];
        }
        $duration = $video_info['items'][0]['contentDetails']['duration'];
        $duration = $this->YTDurationToSeconds($duration);
          $videos[$video_info['items'][0]['id']]['duration'] = $duration;
          $video = $videos[$video_info['items'][0]['id']];
          if($video['index'] == 0) {
            $first_video = $video;
          }
        }
    $player_id = 'y-'.time();

    return [
      '#theme' => 'youtube_playlist',
      '#video' => $first_video,
      '#playlist' => $videos,
      '#player_id' => $player_id,
      '#attached' => [
        'library' => [
          'youtube_playlist/youtube_playlist',
        ],
        'drupalSettings' => [
          'video' => $first_video,
          'videos' => $videos,
          'player_id' => $player_id,
        ],
      ],
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
  }

}