<?php

namespace Drupal\convert_media_tags_to_markup\ConvertMediaTagsToMarkup;

use Drupal\convert_media_tags_to_markup\traits\Singleton;
use Drupal\convert_media_tags_to_markup\traits\CommonUtilities;

/**
 * Represents the Closest Zip Code API.
 */
class App {

  const MEDIA_WYSIWYG_TOKEN_REGEX = '/\[\[\{.*?"type":"media".+?\}\]\]/s';

  use Singleton;
  use CommonUtilities;

  /**
   * Filter text.
   *
   * The is the heart of this module: change media tags code to an actual
   * image tag. This can be done by the filter plugin or by the DbReplacer
   * object.
   *
   * @param string $text
   *   The text to filter.
   *
   * @return string
   *   The filtered text.
   *
   * @throws Exception
   */
  public function filterText(string $text) : string {
    $rendered_text = $text;
    $count = 1;
    preg_match_all(self::MEDIA_WYSIWYG_TOKEN_REGEX, $text, $matches);
    if (!empty($matches[0])) {
      foreach ($matches[0] as $match) {
        $replacement = $this->tokenToMarkup(array($match), FALSE);
        $rendered_text = str_replace($match, $replacement, $rendered_text, $count);
      }
    }
    return $rendered_text;
  }

  /**
   * Replace callback to convert a media file tag into HTML markup.
   *
   * This code is adapted from
   * http://cgit.drupalcode.org/media/tree/modules/media_wysiwyg/includes/media_wysiwyg.filter.inc?h=7.x-3.x.
   *
   * @param string $match
   *   Takes a match of tag code.
   * @param bool $wysiwyg
   *   Set to TRUE if called from within the WYSIWYG text area editor.
   *
   * @return string
   *   The HTML markup representation of the tag, or an empty string on failure.
   */
  public function tokenToMarkup($match, $wysiwyg = FALSE) {
    try {
      $match = str_replace("[[", "", $match);
      $match = str_replace("]]", "", $match);
      $tag = $match[0];

      if (!is_string($tag)) {
        throw new \Exception('Unable to find matching tag');
      }

      $tag_info = $this->drupalJsonDecode($tag);
      if (!isset($tag_info['fid'])) {
        throw new \Exception('No file Id');
      }

      $file = $this->fileLoad($tag_info['fid']);
      $uri = $file->getFileUri();
      $filepath = file_create_url($uri);
      $alt = empty($tag_info['attributes']['alt']) ? '' : $tag_info['attributes']['alt'];
      $title = $alt;
      $height = empty($tag_info['attributes']['height']) ? '' : 'height="' . $tag_info['attributes']['height'] . '"';
      $width = empty($tag_info['attributes']['width']) ? '' : 'width="' . $tag_info['attributes']['width'] . '"';
      $class = empty($tag_info['attributes']['class']) ? '' : $tag_info['attributes']['class'];
      $style = empty($tag_info['attributes']['style']) ? '' : $tag_info['attributes']['style'];
      $output = '
      <div class="media media-element-container media-default">
        <div id="file-' . $tag_info['fid'] . '" class="file file-image">
          <div class="content">
            <img style="' . $style . '" alt="' . $alt . '" title="' . $title . '" class="' . $class . '" src="' . $filepath . '" ' . $height . ' ' . $width . '>
          </div>
        </div>
      </div>';
      return $output;
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return '';
    }
  }

}
