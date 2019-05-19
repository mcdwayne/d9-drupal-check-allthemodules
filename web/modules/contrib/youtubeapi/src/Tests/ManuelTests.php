<?php
/**
 * Created by PhpStorm.
 * User: nwanasinghe
 * Date: 16/12/2016
 * Time: 14:39
 */

namespace Drupal\youtubeapi\Tests;


use Drupal\youtubeapi\YoutubeAPI\YoutubeCaptions;
use Drupal\youtubeapi\YoutubeAPI\YoutubeSearch;
use Drupal\youtubeapi\YoutubeAPI\YoutubeVideos;

class ManuelTests {

  /**
   * Display result array.
   */
  public $display_result_array = FALSE;

  /**
   * Result.
   */
  private $result = NULL;

  /**
   * runTests() Run this function to test your API.
   */

  public static function runTests() {

    $test = new ManuelTests();
    $test->display_result_array = TRUE;

    //Search videos
    $result = $test->search('drupal');
    if (empty($result['items'][0]['id']['videoId'])) {
      return FALSE;
    }
    //Read First item's ID
    $videoId = $result['items'][0]['id']['videoId'];


    //Get A video info (caption)
    $result = $test->caption($videoId);


    //Get A video details (caption)
    $result = $test->videos($videoId);
    if (empty($result['items'][0]['snippet']['channelId'])) {
      return FALSE;
    }
    //Read the channel ID
    $channelId = $result['items'][0]['snippet']['channelId'];

    //Get a channels's Videos (Using search function)
    $test->searchChannelVideos($channelId);


    return TRUE;
  }

  /**
   * Search Video Example
   */
  public function search($q) {

    $yt = new YoutubeSearch();
    $yt->addQuery(YoutubeSearch::q, $q);
    $yt->addQuerys([
      YoutubeSearch::part => 'id',
      YoutubeSearch::type => 'video',
      YoutubeSearch::maxResults => 1,
    ]);
    $this->result = $yt->execute();

    $this->show();
    return $this->result;
  }

  /**
   * Get A channel's videos list. Ex : channelID : UCanC-yCs3G1goz3CxMvgVAg
   */
  public function searchChannelVideos($channelId) {

    $yt = new YoutubeSearch();
    $yt->addQuerys([
      YoutubeSearch::part => 'id',
      YoutubeSearch::channelId => $channelId,
      YoutubeSearch::maxResults => 2,
    ]);
    $this->result = $yt->execute();

    $this->show();
    return $this->result;
  }

  /**
   * Get Caption Example : Video ID rF1X12PE6PY and XgYu7-DQjDQ
   */
  public function caption($videoId) {

    $yt = new YoutubeCaptions();
    $yt->addQuery(YoutubeCaptions::part, 'id,snippet');
    $yt->addQuery(YoutubeCaptions::videoId, $videoId);
    $this->result = $yt->execute();

    $this->show();
    return $this->result;
  }

  /**
   * Get Video details. Example : Video ID rF1X12PE6PY
   */
  public function videos($videoId) {

    $yt = new YoutubeVideos();
    $yt->addQuery(YoutubeVideos::part, 'contentDetails,snippet');
    $yt->addQuery(YoutubeVideos::id, $videoId);
    $this->result = $yt->execute();

    $this->show();
    return $this->result;
  }


  /**
   * Show Result.
   */
  private function show($force = FALSE) {
    if ($this->display_result_array || $force) {
      echo json_encode($this->result, JSON_PRETTY_PRINT);
    }
  }

}