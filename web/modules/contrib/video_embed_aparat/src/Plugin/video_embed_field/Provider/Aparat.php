<?php

/**
 * @file
 * Contains \Drupal\video_embed_aparat\Plugin\video_embed_field\Provider\Aparat.
 * @author Hadi Mollaei <mr.hadimollaei@gmail.com>
 */

namespace Drupal\video_embed_aparat\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use Guzzle\Http\Client;
use Drupal\Component\Utility\SafeMarkup;

/**
 * @VideoEmbedProvider(
 *   id = "aparat",
 *   title = @Translation("Aparat")
 * )
 */
class Aparat extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'scrolling' => 'no',
        'webkitAllowFullScreen' => 'webkitAllowFullScreen',
        'mozallowfullscreen' => 'mozallowfullscreen',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('http://www.aparat.com/video/video/embed/videohash/%s/vt/frame', $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $image_url = $this->aparat_get_thumbnail_url($this->getVideoId());
    if ($image_url) {
      return $image_url;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
   public static function getIdFromInput($input) {
	$urltoarray = explode("/" , $input);
	$id = end($urltoarray);
	if($id) return $id;
	return FALSE;
  } 
  
  /**
   * 
   * @param type $id
   * @return boolean|array
   */
  public static function aparat_get_thumbnail_url($id) {
    $url = 'http://www.aparat.com/etc/api/video/videohash/' . $id;
    $client = \Drupal::httpClient();
    $response = $client->get($url);
    $response_body = json_decode($response->getBody());
	if(isset($response->video->big_poster)) {
		return $response->video->big_poster;
	}
	return FALSE;
  }
  
}