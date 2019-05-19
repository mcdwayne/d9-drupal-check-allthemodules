<?php

namespace Drupal\url_to_video_filter\Service;

/**
 * Service class for the URL To Video Filter module.
 *
 * Performs various actions used by the module.
 */
class UrlToVideoFilterService implements UrlToVideoFilterServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function convertYouTubeUrls($text) {
    $return = [
      'text' => $text,
      'url_found' => FALSE,
    ];

    $urls = $this->parseYouTubeUrls($text);
    if (count($urls)) {
      $return['url_found'] = TRUE;
      foreach ($urls as $url) {
        $embed_code = $this->convertYouTubeUrlToEmbedCode($url);
        $return['text'] = str_replace($url, $embed_code, $return['text']);
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function convertVimeoUrls($text) {
    $return = [
      'text' => $text,
      'url_found' => FALSE,
    ];

    $urls = $this->parseVimeoUrls($text);
    if (count($urls)) {
      $return['url_found'] = TRUE;
      foreach ($urls as $url) {
        $embed_code = $this->convertVimeoUrlToEmbedCode($url);
        $return['text'] = str_replace($url, $embed_code, $return['text']);
      }
    }

    return $return;
  }

  /**
   * Parses text for YouTube URLs.
   *
   * @param string $text
   *   The text to parse for YouTube URLs.
   *
   * @return array
   *   An array containing any YouTube URLs found in the text
   */
  protected function parseYouTubeUrls($text) {
    $urls = [];

    $youtube_regex = '/(^|\b)http(s)?:\/\/(www\.)?youtube\.com\/watch.+?(?=($|\s|\r|\r\n|\n|<))/m';
    preg_match_all($youtube_regex, $text, $matches);
    $urls = array_merge($urls, $matches[0]);

    $youtube_regex = '/(^|\b)http(s)?:\/\/(www\.)?youtu\.be\/.+?(?=($|\s|\r|\r\n|\n|<))/m';
    preg_match_all($youtube_regex, $text, $matches);
    $urls = array_merge($urls, $matches[0]);

    $youtube_regex = '/(^|\b)http(s)?:\/\/(www\.)?youtube\.com\/embed.+?(?=($|\s|\r|\r\n|\n|<))/m';
    preg_match_all($youtube_regex, $text, $matches);
    $urls = array_merge($urls, $matches[0]);

    return $urls;
  }

  /**
   * Converts YouTube URL to HTML placeholders.
   *
   * Note that ths function only embeds placeholders into the HTML. The actual
   * content, whether that be a clickable thumbnail, or the embedded video
   * itself, is injected into the HTML with JavaScript, replacing the
   * placeholders this function embeds.
   *
   * Converts for URLs of the following patterns:
   *   - youtube.com/watch?v=##########
   *   - youtube.com/embed/###########
   *   - youtu.be/###########
   *
   * @param string $url
   *   The YouTube URL to convert.
   *
   * @return string
   *   HTML containing the placeholder for the YouTube video for the given URL.
   */
  protected function convertYouTubeUrlToEmbedCode($url) {
    $embed_code = '';

    if (strpos($url, 'youtube.com/watch')) {
      $video_key = preg_replace('/(http)?(s)?(:\/\/)?(www\.)?youtube\.com\/watch\?v=/', '', $url);
    }
    elseif (strpos($url, 'youtube.com/embed')) {
      $video_key = preg_replace('/(http)?(s)?(:\/\/)?(www\.)?youtube\.com\/embed\//', '', $url);
    }
    elseif (strpos($url, 'youtu.be')) {
      $video_key = preg_replace('/(http)?(s)?(:\/\/)?(www\.)?youtu.be\//', '', $url);
    }

    $embed_code .= '<span class="url-to-video-container youtube-container no-js">';
    $embed_code .= '<span class="youtube-player url-to-video-player loader" data-youtube-id="' . $video_key . '"></span>';
    $embed_code .= '</span>';

    return $embed_code;
  }

  /**
   * Parses text for Vimeo URLs.
   *
   * @param string $text
   *   The text to parse for Vimeo URLs.
   *
   * @return array
   *   An array containing any Vimeo URLs found in the text
   */
  protected function parseVimeoUrls($text) {
    $vimeo_regex = '/(^|\b)http(s)?:\/\/(www\.)?vimeo\.com.+?(?=($|\s|\r|\r\n|\n|<))/m';
    preg_match_all($vimeo_regex, $text, $matches);

    return $matches[0];
  }

  /**
   * Converts Vimeo URL to HTML placeholders.
   *
   * Note that ths function only embeds placeholders into the HTML. The actual
   * content, whether that be a clickable thumbnail, or the embedded video
   * itself, is injected into the HTML with JavaScript, replacing the
   * placeholders this function embeds.
   *
   * @param string $url
   *   The Vimeo URL to convert.
   *
   * @return string
   *   HTML containing the placeholder for the Vimeo video for the given URL.
   */
  protected function convertVimeoUrlToEmbedCode($url) {
    $embed_code = '';

    $video_key = preg_replace('/(http)?(s)?(:\/\/)?(www\.)?vimeo\.com\//', '', $url);
    $embed_code .= '<span class="url-to-video-container vimeo-container no-js">';
    $embed_code .= '<span class="vimeo-player url-to-video-player loader" data-vimeo-id="' . $video_key . '"></span>';
    $embed_code .= '</span>';

    return $embed_code;
  }

}
