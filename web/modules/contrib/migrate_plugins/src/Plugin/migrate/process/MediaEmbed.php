<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'MediaTag' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "media_embed"
 * )
 */
class MediaEmbed extends ProcessPluginBase {

  const MEDIA_TOKEN_REGEX = '/\[\[\{.+?"type":"media".+?\}\]\]/s';

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $nid = $row->getSource()['nid'];
    $processed_text = $value;
    $count = 1;
    preg_match_all(self::MEDIA_TOKEN_REGEX, $value, $matches);
    if (!empty($matches[0])) {
      foreach ($matches[0] as $match) {
        $replacement = $this->process_media_tag($match, $nid);
        $processed_text = str_replace($match, $replacement, $processed_text, $count);
      }
    }

    return $processed_text;
  }

  /**
   * @param $match
   * @return null|string
   */
  protected function process_media_tag($match, $nid) {
    $tag = str_replace(array('[[', ']]'), '', $match);
    $tag_info = json_decode($tag);
    $attributes = [];
    $styles = [];
    $query = Database::getConnection()->select('media', 'm')->fields('m', ['uuid']);
    $query->leftJoin('migrate_map_iul_media', 'mm', 'm.mid = mm.destid1');
    $query->condition('mm.sourceid1', $tag_info->fid);

    if ($uuid = $query->execute()->fetchField()) {
      // Add styles to array.
      if (isset($tag_info->attributes->style)) {
        $styles_string = explode(";", $tag_info->attributes->style);
        foreach ($styles_string as $style) {
          if (strpos($style, ':')) {
            list($k, $v) = explode(":", $style);
            $styles[trim($k)] = trim($v);
          }
        }

        // Set data-align.
        if (isset($styles['float'])) {
          $attributes['data-align'] = $styles['float'];
        }
      }

      $attributes['data-entity-uuid'] = $uuid;
      if (isset($styles['float'])) {
        $attributes['data-align'] = $styles['float'];
      }

      // Height & width from inline styles or fall back to attributes.
      $width = isset($styles['width']) ? $styles['width'] : (isset($tag_info->attributes->width) ? $tag_info->attributes->width : 0);
      $height = isset($styles['height']) ? $styles['height'] : (isset($tag_info->attributes->height) ? $tag_info->attributes->height : 0);
      $width = (int) str_replace('px', '', $width);
      $height = (int) str_replace('px', '', $height);

      // @todo if width = 0, load file entity and determine dimensions from
      // database (for now we'll assume one-third is the default).
      $image_style = $this->calculate_image_style($width, $height);

      // drush_print("image_style for $width x $height is $image_style");

      // if ($width == 0) {
      //   var_dump($match);
      //   var_dump($tag_info);
      // }

      $attributes['data-embed-button'] = 'media_content';
      $attributes['data-entity-embed-display'] = 'entity_reference:media_thumbnail';
      $attributes['data-entity-embed-display-settings'] = "{&quot;image_style&quot;:&quot;{$image_style}&quot;,&quot;image_link&quot;:&quot;&quot;}";
      $attributes['data-entity-type'] = 'media';

      return '<drupal-entity '. join(' ', array_map(function($key) use ($attributes) {
          return $key.'="'.$attributes[$key].'"';
        }, array_keys($attributes))).' ></drupal-entity>';
    }

    \Drupal::logger('migrate_plugins')->warning('Media File not found when importing %nid', ['%nid' => $nid]);
    return NULL;
  }

  /**
   * Calculate the D7 image style to use based on the D7 embed code's
   * dimensions.
   *
   * @param $width
   * @return string
   */
  protected function calculate_image_style($width, $height) {

    $image_style = 'iu_one_third';
    if (empty($width) || empty($height)) {
      return $image_style;
    }

    // Define the image's aspect ratio and fall back to a the IU standard
    // of 3:2 if height or width is unspecified. Note I'm inverting the ratio
    // to make it a fraction between 0 - 1 if landscape and greater than 1 if
    // standard, to make the logic easier.
    $aspect = $height / $width;

    // Standard content image styles:
    // One third & One half (768 uncropped height for tall images)
    // One-third & one-half 1:1 (768×768)
    // One-third & one-half 2:1 (768×384)
    // One-third & one-half 3:2 (768×512)
    // Two thirds & Full 3:2 (1024×512)
    // Two thirds & Full 4:3 (1024×768)

    // Image styles
    // Image style prefix (width) breakdown:
    // 0             100           502        infinity
    // |   thumbnail  |  one_third  | one_half  |  two_thirds  |  full  |

    $thumbnail = 100;
    $full_width = 861; // D7 content column width.
    $one_third = ceil($full_width / 3); // 287
    $half_width = ceil($full_width / 2);  // 431
    $two_thirds = ceil($full_width * 2 / 3); // 574

    // Image style suffix (height) breakdown:
    //      wide (2:1) |  (standard) 3:2  |  (screen) 4:3  |  (square) 1:1
    //      ----------------------------------------------------------------
    // 768  |   x384   |       x512       |       x768     |   blank
    // 1024 |   x512            |      x768

    $square = 1;
    $standard = 2 / 3; // Classic 35mm still photograph format.
    $screen = 3 / 4; // Traditional computer screen dimensions.
    $wide = 1 / 2; // Univisium
    $portrait = 3 / 2;

    switch ($width) {

      // Special case for very small images.
      case $width <= $this->fuzzy_median($thumbnail, $one_third):
        $image_style = 'thumbnail';
        break;


      // Images between thumbnail-size and two-thirds column width (including
      // one-third and one-half content column width) share the same height
      // logic.
      case ($this->fuzzy_median($thumbnail, $one_third) < $width)
        && ($width <= $this->fuzzy_median($half_width, $two_thirds)) :


        if ($width <= $this->fuzzy_median($one_third, $half_width)) {
          $image_style = 'iu_one_third';
        }
        else {
          $image_style = 'iu_one_half';
        }

        // Images wider than standard (3:2) are sized to wide (2:1) [768x384].
        if ($aspect <= $this->fuzzy_median($wide, $standard)) {
          $image_style .= '_wide';
        }

        // Images between standard (3:2) and square (1:1) are sized to
        // standard [768x512].
        elseif ($aspect <= $this->fuzzy_median($standard, $square)) {
          $image_style .= '_standard';
        }

        // Images between square (1:1) and portrait (2:3) are sized to square
        // [768x768].
        elseif ($aspect <= $this->fuzzy_median($square, $portrait)) {
          $image_style .= '_square';
        }
        break;


      // Images greater than one-half of content column (including two-thirds
      // and full width styles) share the same height logic.
      case ($this->fuzzy_median($half_width, $two_thirds) < $width) :

        if ($width <= $this->fuzzy_median($two_thirds, $full_width)) {
          $image_style = 'iu_two_thirds';
        }
        else {
          $image_style = 'iu_full';
        }

        // Images that are less than screen size (4:3) are sized to wide (2:1)
        // [1024x512].
        if ($aspect <= $this->fuzzy_median($wide, $screen)) {
          $image_style .= '_wide';
        }
        // Anything bigger than that is sized to screen (4:3) [1024x768].
        else {
          $image_style .= '_screen';
        }
        break;

      default:
        $image_style = 'iu_one_third';
        break;
    }
    return $image_style;
  }

  /**
   * Calculate the median number between two integers.
   * value $a should always be smaller than $b
   */
  private function fuzzy_median($a, $b) {
    if ($b > $a) {
      $c = $a + floor(($b - $a) / 2);
    }
    else {
      $c = $b + floor(($a - $b) / 2);
    }
    return $c;
  }

}
