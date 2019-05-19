<?php

namespace Drupal\transcript\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Xss;

/**
 * Provides a block to show video transcript.
 *
 * @Block(
 *   id = "block_youtube_video_transcript",
 *   admin_label = @Translation("Youtube Video Transcript"),
 * )
 */
class TranscriptBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = $this->transcript_show();
    if(!empty($output)) {
      return [
        '#theme' => 'transcript_youtube',
        '#var' => $output,
        '#attached' => array(
          'library' => array(
            'transcript/transcript',
          ),
        ),
      ];
    } else {
      return [
        '#theme' => 'transcript_error',
      ];
    }
  }

  public function transcript_show() {
    $auto_play = \Drupal::config('transcript.settings')->get('transcript_video_auto_play');
    $width = \Drupal::config('transcript.settings')->get('transcript_iframe_width');
    $height = \Drupal::config('transcript.settings')->get('transcript_iframe_height');
    $video_id = \Drupal::config('transcript.settings')->get('transcript_video_id');
    $lines = $this->transcript_fetch();
    $parameters = array();
    if ($lines) {
      $parameters = array(
        'transcript' => $lines,
        'video_id' => Xss::filter($video_id),
        'auto_play' => Xss::filter($auto_play),
        'width' => Xss::filter($width),
        'height' => Xss::filter($height),
      );
    }
    return $parameters;
  }

  /**
 * Provides functionality for getting transcript using drupal_http_request().
 */
  public function transcript_fetch() {
    $video_id = \Drupal::config('transcript.settings')->get('transcript_video_id');
    $lang = \Drupal::config('transcript.settings')->get('transcript_lang_code');
    $url = 'http://www.youtube.com/api/timedtext?v=' . Xss::filter($video_id) . '&lang=' . Xss::filter($lang);
    $xmlstring = '';
    $lines = array();
    $client = \Drupal::httpClient();
    try {
      /** METHOD-1 **/
      //$response = $client->get($url, array('headers' => array('Accept' => 'text/plain')));
      //$data = (string) $response->getBody();

      /** METHOD-2 **/
      $data = file_get_contents($url);

    } catch(RequestException $e) {
      return $lines;
    }
    if (!empty($data)) {
      $xmlstring = $data;
    }
    if ($xmlstring && substr($xmlstring, 0, 5) == '<?xml') {
      $xml = simplexml_load_string($xmlstring);
      if (is_object($xml) && $xml) {
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);
        $count = count($array['text']);

        for ($i = 0; $i < $count; $i++) {
          foreach ($xml->text[$i]->attributes() as $start) {
            $start_time = $start;
            break;
          }
          $sec = round($start_time, 0);
          $minutes = floor(($sec % 3600) / 60);
          $seconds = $sec % 60;
          if (strlen($seconds) == 1) {
            $seconds = '0' . $seconds;
          }
          if (isset($array['text'][$i]) && $array['text'][$i] != '' && !is_array($array['text'][$i])) {
            $lines[] = array(
              'id' => round($start_time, 0),
              'minute' => $minutes,
              'seconds' => $seconds,
              'txt' => $array['text'][$i],
            );
          }
        }
      }
    }
    return $lines;
  }
}