<?php

namespace Drupal\formatter_suite;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Defines utility functions used throughout the module.
 *
 * <B>Warning:</B> This class is strictly internal to the Formatter Suite
 * module. The class's existance, name, and content may change from
 * release to release without any promise of backwards compatability.
 *
 * @ingroup formatter_suite
 */
final class Utilities {

  /*---------------------------------------------------------------------
   *
   * Color image.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns a data URL for a single-pixel image of the given color.
   *
   * @param string $color
   *   A CSS-style hex color (e.g. #ff00ff).
   *
   * @return string
   *   Returns a data URL string containing a 1-pixel PNG image using
   *   the given color.
   */
  public static function createImage($color) {
    // Parse color.
    if (strlen($color) === 4) {
      list($r, $g, $b) = sscanf($color, "#%1x%1x%1x");
      $r *= 16;
      $g *= 16;
      $b *= 16;
    }
    else {
      list($r, $g, $b) = sscanf($color, "#%2x%2x%2x");
    }

    // Create an image and set its color to the given color.
    $image = @imagecreatetruecolor(1, 1);
    $background = @imagecolorallocate($image, $r, $g, $b);
    imagesetpixel($image, 0, 0, $background);

    // Output the image into a binary string.
    ob_start();
    imagepng($image, NULL, 0);
    $data = ob_get_contents();
    ob_end_clean();

    // Convert the binary image string to a base 64 PNG data URL.
    return 'data:image/png;base64,' . base64_encode($data);
  }

  /*---------------------------------------------------------------------
   *
   * Formatting.
   *
   *---------------------------------------------------------------------*/

  /**
   * Formats a number with a bytes suffix.
   *
   * @param mixed $number
   *   The number to format.
   * @param int $kunit
   *   (optional, default = 1000) Either 1000 (ISO standard kilobyte) or
   *   1024 (legacy kibibyte).
   * @param bool $fullWord
   *   (optional, default = FALSE) When FALSE, use an abbreviation suffix,
   *   like "KB". When TRUE, use a full word suffix, like "Kilobytes".
   * @param int $decimalDigits
   *   (optional, default = 2) The number of decimal digits for the resulting
   *   number.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns a representation of the number, along with a suffix
   *   that summarizes the value using the selected 'k' units.
   *
   * @internal
   * Drupal's format_size() function is widely used to format values in
   * units of kilobytes, megabytes, etc. Unfortunately, it considers a
   * "kilobyte" equal to 1024 bytes, which is not compliant with the
   * 1998 International Electrotechnical Commission (IEC) and
   * International System of Quantities (ISO) standards. By those standards,
   * a "kilobyte" equals 1000 bytes. This definition retains the SI units
   * convention that "k" is for 1000 (e.g. kilometer, kilogram).
   *
   * The following code is loosely based upon format_size(). It corrects
   * the math for the selected "k" units (kilo or kibi) and uses the
   * corresponding correct suffixes. It also supports a requested number
   * of decimal digits.
   *
   * The following code also ignores the $langcode, which format_size()
   * incorrectly uses to make the output translatable - even though it is
   * NOT subject to translation since it is numeric and the suffixes are
   * defined by international standards.
   * @endinternal
   */
  public static function formatBytes(
    $number,
    int $kunit = 1000,
    bool $fullWord = FALSE,
    int $decimalDigits = 2) {

    // Validate.
    switch ($kunit) {
      case 1000:
      case 1024:
        $kunit = (float) $kunit;
        break;

      default:
        $kunit = 1000.0;
        break;
    }

    if ($decimalDigits < 0) {
      $decimalDigits = 0;
    }

    if ($number < $kunit) {
      // The quantity is smaller than the 'k' unit. Report it as-is.
      return \Drupal::translation()->formatPlural(
        $number,
        '1 byte',
        '@count bytes');
    }

    // Find the proper 'k'. Values larger than the largest 'k' unit
    // will just be large numbers ahead of the largest prefix (e.g.
    // "879 YB").
    $value = (((float) $number) / $kunit);
    foreach (['K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'] as $unit) {
      if ($value < $kunit) {
        // The value is now less than the next unit, stop reducing.
        break;
      }

      $value /= $kunit;
    }

    // Simplify the remainder with the proper number of decimal digits.
    $fmt = '%1.' . $decimalDigits . 'f';
    $formattedValue = sprintf($fmt, $value);

    // If using abbrevations, return the formatted value and suffix.
    if ($fullWord === FALSE) {
      // Use abbreviations for suffixes.
      switch ($kunit) {
        default:
        case 1000:
          $suffix = $unit . 'B';
          break;

        case 1024:
          $suffix = $unit . 'iB';
          break;
      }

      return new FormattableMarkup(
        '@value @suffix',
        [
          '@value'  => $formattedValue,
          '@suffix' => $suffix,
        ]);
    }

    // When using full words, return the formatted value and word,
    // using singular and plural forms as appropriate.
    $singular = [];
    $plural   = [];
    switch ($kunit) {
      default:
      case 1000:
        $singular = [
          'K' => 'Kilobyte',
          'M' => 'Megabyte',
          'G' => 'Gigabyte',
          'T' => 'Terabyte',
          'P' => 'Petabyte',
          'E' => 'Exabyte',
          'Z' => 'Zettabyte',
          'Y' => 'Yottabyte',
        ];
        $plural = [
          'K' => 'Kilobytes',
          'M' => 'Megabytes',
          'G' => 'Gigabytes',
          'T' => 'Terabytes',
          'P' => 'Petabytes',
          'E' => 'Exabytes',
          'Z' => 'Zettabytes',
          'Y' => 'Yottabytes',
        ];
        break;

      case 1024:
        $singular = [
          'K' => 'Kibibyte',
          'M' => 'Mibibyte',
          'G' => 'Gibibyte',
          'T' => 'Tebibyte',
          'P' => 'Pebibyte',
          'E' => 'Exbibyte',
          'Z' => 'Zebibyte',
          'Y' => 'Yobibyte',
        ];
        $plural = [
          'K' => 'Kibibytes',
          'M' => 'Mibibytes',
          'G' => 'Gibibytes',
          'T' => 'Tebibytes',
          'P' => 'Pebibytes',
          'E' => 'Exbibytes',
          'Z' => 'Zebibytes',
          'Y' => 'Yobibytes',
        ];
        break;
    }

    return new FormattableMarkup(
      '@value @suffix',
      [
        '@value'  => $formattedValue,
        '@suffix' => ($value < 1) ? $singular[$unit] : $plural[$unit],
      ]);
  }

}
