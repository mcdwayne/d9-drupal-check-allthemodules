<?php

namespace Drupal\twig_tools\TwigExtension;

/**
 * Class TwigConvert.
 */
class TwigColor extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('rgb_to_hex', [$this, 'rgbToHex']),
      new \Twig_SimpleFilter('css_rgb_to_hex', [$this, 'cssRgbToHex']),
      new \Twig_SimpleFilter('hex_to_rgb', [$this, 'hexToRgb']),
      new \Twig_SimpleFilter('hex_to_css_rgb', [$this, 'hexToCssRgb']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_tools_color.twig.extension';
  }

  /**
   * Returns the hexadecimal color value of an RGB array of values.
   *
   * @param array $rgb
   *   An array of RGB (red, green, blue) values.
   *
   * @return string
   *   The hexadecimal color equivalent of the passed RGB color.
   */
  public static function rgbToHex(array $rgb) {

    $rgb = array_reduce($rgb, function ($carry, $color) {
      $options = [
        'options' => ['min_range' => 0, 'max_range' => 255],
      ];

      // Validate that each value is between 0-255.
      $int_color = filter_var($color, FILTER_VALIDATE_INT, $options);

      if ($int_color !== FALSE) {
        $carry[] = $int_color;
      }

      return $carry;

    }, []);

    if (count($rgb) === 3) {
      return sprintf("#%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2]);
    }

  }

  /**
   * Returns the hexadecimal color value of a passed CSS RGB color string.
   *
   * @param string $string
   *   The variable to get the hexadecimal color equivalent of.
   *
   * @return string
   *   The hexadecimal color equivalent of the passed CSS RGB color string.
   */
  public static function cssRgbToHex($string) {
    // Match a 'rgb(xxx,xxx,xxx)' string pattern.
    $group_pattern = '([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])';
    $pattern = '/.*[rgb]\(' . $group_pattern . ',\s*' . $group_pattern . ',\s*' . $group_pattern . '\).*/';

    preg_match($pattern, $string, $rgb);

    if (is_array($rgb) && count($rgb) === 4) {

      // Remove the first array element which has the original full string.
      array_shift($rgb);

      return self::rgbToHex($rgb);
    }
  }

  /**
   * Returns an array of equivalent RGB values of the passed hexadecimal color.
   *
   * @param string $hex
   *   The hexadecimal color to get the RGB equivalent colors values of.
   *
   * @return array
   *   The array of equivalent equivalent RGB color values.
   */
  public static function hexToRgb($hex) {

    // Regex pattern validating hexadecimal colors.
    $pattern = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    preg_match($pattern, $hex, $match);

    if (isset($match[1])) {
      $hex_string = $match[1];

      // Check for shorthand hexadecimal color format.
      if (strlen($hex_string) === 3) {
        $char_array = str_split($hex_string, 1);
        $hex_color = '';

        // Double each character to convert the string to a 6-character
        // hexadecimal color.
        foreach ($char_array as $char) {
          $hex_color = $hex_color . $char . $char;
        }
      }
      else {
        $hex_color = $hex_string;
      }

      // Convert the hexadecimal color string to an array of hexadecimal
      // RGB values.
      $rgb = str_split($hex_color, 2);

      // Convert the array of hexadecimal RGB values to an array of integer
      // RGB values.
      $rgb = array_map('hexdec', $rgb);
      return $rgb;
    }

  }

  /**
   * Returns the CSS RGB string equivalent of the passed hexadecimal color.
   *
   * @param string $hex
   *   The hexadecimal color to get the CSS RGB equivalent string value of.
   *
   * @return string
   *   The CSS RGB equivalent string of the passed hexadecimal color.
   */
  public static function hexToCssRgb($hex) {

    $rgb = self::hexToRgb($hex);
    if (is_array($rgb) && count($rgb) === 3) {
      return sprintf('rgb(%s, %s, %s)', $rgb[0], $rgb[1], $rgb[2]);
    }
  }

}
