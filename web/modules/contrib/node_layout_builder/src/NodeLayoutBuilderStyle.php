<?php

namespace Drupal\node_layout_builder;

use Drupal\node_layout_builder\Helpers\NodeLayoutFileHelper;

/**
 * Class NodeLayoutBuilderStyle.
 *
 * Methods handler html attributes and styles for element.
 */
class NodeLayoutBuilderStyle {

  /**
   * Get attributes HTML (id, class) for element.
   *
   * @param array $attributes
   *   Attributes html elements.
   *
   * @return array
   *   List attributes HTML.
   */
  public static function getAttributes(array $attributes) {
    return [
      'id' => $attributes['container']['id'],
      'class' => $attributes['container']['class'],
    ];
  }

  /**
   * Get styles CSS for element.
   *
   * @param array $styles
   *   Styles element.
   *
   * @return string
   *   Styles CSS.
   */
  public static function getStyles(array $styles) {
    $dimensions = '';
    $font = '';
    $bg_clr = '';
    $bg_img = '';
    $border = '';
    $margin = '';
    $padding = '';

    // Dimensions.
    if (!empty($styles['dimensions']['height'])) {
      $dimensions .= 'height: ' . $styles['dimensions']['height'] . 'px;';
    }

    // Font.
    if (!empty($styles['font']['size'])) {
      $font .= 'font-size: ' . $styles['font']['size'] . 'px;';
    }
    if (!empty($styles['font']['color'])) {
      $font .= 'color: ' . $styles['font']['color'] . ';';
    }

    // Background color.
    if ($styles['background']['bg_enabled'] != 0) {
      $bg_clr .= 'background-color: ' . $styles['background']['color'] . ';';
    }

    // Background image.
    if (!empty($styles['background']['from'])) {
      if ($styles['background']['from'] == 1) {
        if (!empty($styles['background']['image'][0])) {
          $fid = $styles['background']['image'][0];
          $file = NodeLayoutFileHelper::loadFileByFid($fid);
          if ($file) {
            $path = file_create_url($file->getFileUri());
            $bg_img .= 'background-image: url(' . $path . ');';
          }
          if (!empty($styles['background']['img_style'])) {
            $img_style = $styles['background']['img_style'];
            switch ($img_style) {
              case 'cover':
              case 'contain':
                $bg_img .= 'background-repeat: no-repeat;background-size: ' . $img_style . ';';
                break;

              default:
                $bg_img .= 'background-repeat: ' . $img_style . ';';
                break;
            }
          }
          if (!empty($styles['background']['img_position'])) {
            $bg_img .= 'background-position: ' . $styles['background']['img_position'] . ';';
          }
        }
      }
      else {
        $bg_img = 'background-image: url(' . $styles['background']['link'] . ');';

        if (!empty($styles['background']['img_style'])) {
          $img_style = $styles['background']['img_style'];
          switch ($img_style) {
            case 'cover':
            case 'contain':
              $bg_img .= 'background-repeat: no-repeat;background-size: ' . $img_style . ';';
              break;

            default:
              $bg_img .= 'background-repeat: ' . $img_style . ';';
              break;
          }
        }
        if (!empty($styles['background']['img_position'])) {
          $bg_img .= 'background-position: ' . $styles['background']['img_position'] . ';';
        }
      }
    }

    // Border.
    if ($styles['border']['style'] != 'none') {
      $border .= 'border-style: ' . $styles['border']['style'] . ';';
      $border .= 'border-width: ' . $styles['border']['width'] . ';';
      if ($styles['border']['color_enabled'] != 0) {
        $border .= 'border-color: ' . $styles['border']['color'] . ';';
      }
    }

    // Margin.
    if ($styles['margin_padding']['margin']['top'] != '') {
      $margin .= 'margin-top: ' . $styles['margin_padding']['margin']['top'] . 'px;';
    }
    if ($styles['margin_padding']['margin']['right'] != '') {
      $margin .= 'margin-right: ' . $styles['margin_padding']['margin']['right'] . 'px;';
    }
    if ($styles['margin_padding']['margin']['bottom'] != '') {
      $margin .= 'margin-bottom: ' . $styles['margin_padding']['margin']['bottom'] . 'px;';
    }
    if ($styles['margin_padding']['margin']['left'] != '') {
      $margin .= 'margin-left: ' . $styles['margin_padding']['margin']['left'] . 'px;';
    }

    // Padding.
    if ($styles['margin_padding']['padding']['top'] != '') {
      $padding .= 'padding-top: ' . $styles['margin_padding']['padding']['top'] . 'px;';
    }
    if ($styles['margin_padding']['padding']['right'] != '') {
      $padding .= 'padding-right: ' . $styles['margin_padding']['padding']['right'] . 'px;';
    }
    if ($styles['margin_padding']['padding']['bottom'] != '') {
      $padding .= 'padding-bottom: ' . $styles['margin_padding']['padding']['bottom'] . 'px;';
    }
    if ($styles['margin_padding']['padding']['left'] != '') {
      $padding .= 'padding-left: ' . $styles['margin_padding']['padding']['left'] . 'px;';
    }

    if ($dimensions == '' && $font == '' && $bg_clr == '' && $border == '' && $margin == '' && $padding == '') {
      return '';
    }

    return $dimensions . $font . $bg_clr . $bg_img . $border . $margin . $padding;
  }

}
