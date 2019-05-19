<?php

namespace Drupal\youtube_gallery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;

/**
 * Create class for service to use multiple times.
 */
class YoutubeConfig extends ControllerBase {

  /**
   * Pass config to google API.
   */
  public function getGoogleApi() {

    $apikey  = $this->getApiKey();
    $channel = $this->convertChannel();
    $max     = $this->getMaxVideos();

    if ($apikey != NULL && $channel != NULL && $max != NULL) {
      $googleApi = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=" . $max . "&playlistId=" . $channel . "&key=" . $apikey;

      return $googleApi;
    }
    else {
      return NULL;
    }
  }

  /**
   * Encode json content to string.
   */
  public function getYoutubeVideos() {

    $googleApi = $this->getGoogleApi();

    if ($googleApi != NULL) {

      $content = file_get_contents($googleApi);

      $jsonData = Json::decode($content);

      return $jsonData;
    }
  }

  /**
   * Get total videos youtube channel contains.
   */
  public function getTotalVideos() {

    $getVideos = $this->getYoutubeVideos();

    $totalVideos = $getVideos['pageInfo']['totalResults'];

    return Html::escape($totalVideos);
  }

  /**
   * Get Google API key.
   */
  public function getApiKey() {

    $getApiKey = $this->config('youtube_gallery.formsettings')->get('api_key');

    if ($getApiKey != NULL) {
      return $getApiKey;
    }

  }

  /**
   * Get channeId.
   */
  public function getChannelId() {

    $getChannelId = $this->config('youtube_gallery.formsettings')->get('channel_id');

    if ($getChannelId != NULL) {

      return $getChannelId;
    }

  }

  /**
   * Get maxmimum videos to be display.
   */
  public function getMaxVideos() {

    $maxOutput = $this->config('youtube_gallery.formsettings')->get('max_videos');

    if ($maxOutput != NULL) {
      return $maxOutput;
    }

  }

  /**
   * Convert user channel to user uploaded.
   */
  private function convertChannel() {

    $playlistId = str_replace('UC', 'UU', $this->getChannelId());
    return Html::escape($playlistId);
  }

  /**
   * Get Channel Title.
   */
  public function getChannelTitle() {

    $getChannelTitle = $this->getYoutubeVideos();

    $channelTitle = $getChannelTitle['items'][0]['snippet']['channelTitle'];

    return Html::escape($channelTitle);
  }

  /**
   * Get Video Duration.
   */
  public function getVideoDuration($videoId) {

    $url = "https://www.googleapis.com/youtube/v3/videos?id=" . $videoId . "&part=contentDetails&key=" . $this->getApiKey();

    $getContent = file_get_contents($url);

    $decodeJson = Json::decode($getContent);

    $duration = $decodeJson['items'][0]['contentDetails']['duration'];

    $convertedTime = $this->convertTime($duration);

    return Html::escape($convertedTime);

  }

  /**
   * Get Current video that display on page.
   */
  public function getCurrentVideo($videoId) {

    $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet&id=$videoId&key=" . $this->getApiKey();

    $content = file_get_contents($url);

    $jsonDecode = Json::decode($content);

    $output = [];

    $output['videoId'] = $jsonDecode['items'][0]['id'];
    $output['title'] = $jsonDecode['items'][0]['snippet']['title'];
    $output['description'] = $jsonDecode['items'][0]['snippet']['description'];
    $output['publishedAt'] = $jsonDecode['items'][0]['snippet']['publishedAt'];

    return $output;
  }

  /**
   * Convert youtube time in human readable format.
   */
  private function convertTime($youtubeTime) {

    $start = new \DateTime('@0');

    $start->add(new \DateInterval($youtubeTime));

    $newFormat = $start->format('H:i:s');

    if (substr($newFormat, 0, 2) == "00") {
      $duration = substr($newFormat, 3);
    }
    else {
      $duration = $newFormat;
    }
    return $duration;

  }

  /**
   * Getting client Oauth id.
   */
  public function getClientId() {

    $client_id = $this->config('youtube_gallery.formsettings')->get('client_id');
    return $client_id;

  }

  /**
   * Getting client Oauth secret.
   */
  public function getClientSecret() {

    $client_secret = $this->config('youtube_gallery.formsettings')->get('client_secret');
    return $client_secret;
  }

}
