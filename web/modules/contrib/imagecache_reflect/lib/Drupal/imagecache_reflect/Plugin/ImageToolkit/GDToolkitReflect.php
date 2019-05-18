<?php

/**
 * @file
 * Contains \Drupal\imagecache_reflect\Plugin\ImageToolkit\GDToolkitReflect.
 */

namespace Drupal\imagecache_reflect\Plugin\ImageToolkit;

use Drupal\Core\Image\ImageInterface;

/**
 * Creates a reflect operation for the GD library.
 * 
 * @todo Register this class as a GDToolkit operation plugin.
 */
class GDToolkitReflect {

  /**
   * Creates an image with a reflection-like effect from a provided image.
   * 
   * @param resource $image
   *   The image object to create the reflected image from.
   * @param array $configuration
   *   An associative array containing:
   *   -color: Hex color of the reflection background.
   *   -transparency: Boolean. Whether to preserve alpha transparency.
   *   -position: Reflection position (left, right, top, bottom).
   *   -size: Size of the reflection in percent or pixels.
   * 
   * @return bool
   *   Returns TRUE if successful.
   */
  public static function reflect(ImageInterface $image, array $configuration) {
    // Verify that Drupal is using the PHP GD library for image manipulations
    // since this effect depends on functions in the GD library.
    if ($image->getToolkitId() != 'gd') {
      watchdog('image', 'Image reflect failed on %path. Using non GD toolkit.', array('%path' => $image->getSource()), WATCHDOG_ERROR);
      return FALSE;
    }    

    // Get config params.
    $color = $configuration['bgcolor'];
    $transparency = $configuration['transparency'];
    $position = $configuration['position'];
    $size = $configuration['size'];

    // Image source for the reflection effect.
    $src_im = $image->getResource();

    // Expand the canvas vertically or horizontally depending on
    // the reflection position.
    $is_vertical = \in_array($position, array('top', 'bottom'));

    // Determine the reflection size in pixels.
    // Calculate pixels from a percentage if given.
    if (preg_match('/^\d{1,3}%$/', $size)) {
      $image_size = $is_vertical ? $image->getHeight() : $image->getWidth();
      $size = $image_size * (floatval($size) / 100);
    }
    else {
      $size = intval($size);
    }

    // Calculate the final dimensions of the image with its reflection.
    $width = $image->getWidth() + ($is_vertical ? 0 : $size);
    $height = $image->getHeight() + ($is_vertical ? $size : 0);

    // Creates a new destination canvas.
    $dst_im = imagecreatetruecolor($width, $height);

    // If $color is empty, we're trying for a transparent canvas:
    if (empty($color)) {
      imagesavealpha($dst_im, TRUE);
      imagealphablending($dst_im, FALSE);
    }
    // Otherwise colorize the new canvas.
    else {
      // Convert short #FFF syntax to full #FFFFFF syntax.
      if (strlen($color) == 4) {
        $c = $color;
        $color = $c[0] . $c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3];
      }
      // Convert #FFFFFF syntax to hexadecimal colors.
      $color = hexdec(str_replace('#', '0x', $color));
      imagefill($dst_im, 0, 0, $color);
    }

    // Determine x,y coordinates where source will be on the new image canvas.
    $x = $position == 'left' ? $size : 0;
    $y = $position == 'top' ? $size : 0;

    // Copy the source image onto the new canvas.
    imagecopy($dst_im, $src_im, $x, $y, 0, 0, $image->getWidth(), $image->getHeight());

    // If the user has said that the source image might contain transparency,
    // use the slower, but working algorithm to merge the images.
    $minalpha = $transparency ? self::alphaGetMin($src_im) : 0;

    // Determines the number of passes for imagecopymergeAlpha().
    $steps = $is_vertical ? min($size, $image->getHeight()) : min($size, $image->getWidth());;

    // Creates the reflection on the new canvas.
    switch ($position) {
      case 'top':
        for ($i = 0, $opacity = 50; $i < $steps; ++$i, $opacity = ceil(((($steps - $i) / $steps) * 100) / 2)) {
          self::imagecopymergeAlpha($dst_im, $src_im, 0, $y - $i, 0, $i, $image->getWidth(), 1, $opacity, $minalpha);
        }
        break;
      case 'bottom':
        for ($i = 0, $opacity = 50; $i < $steps; ++$i, $opacity = ceil(((($steps - $i) / $steps) * 100) / 2)) {
          self::imagecopymergeAlpha($dst_im, $src_im, 0, $image->getHeight() + $i, 0, $image->getHeight() - $i - 1, $image->getWidth(), 1, $opacity, $minalpha);
        }
        break;
      case 'left':
        for ($i = 0, $opacity = 50; $i < $steps; ++$i, $opacity = ceil(((($steps - $i) / $steps) * 100) / 2)) {
          self::imagecopymergeAlpha($dst_im, $src_im, $x - $i, 0, $i, 0, 1, $image->getHeight(), $opacity, $minalpha);
        }
        break;
      case 'right':
        for ($i = 0, $opacity = 50; $i < $steps; ++$i, $opacity = ceil(((($steps - $i) / $steps) * 100) / 2)) {
          self::imagecopymergeAlpha($dst_im, $src_im, $image->getWidth() + $i, 0, $image->getWidth() - $i - 1, 0, 1, $image->getHeight(), $opacity, $minalpha);
        }
        break;

    }

    // Destroy the original image and return the modified image.
    imagedestroy($image->getResource());
    $image
      ->setResource($dst_im)
      ->setWidth($width)
      ->setHeight($height);
    return TRUE;
  }  
    
  /**
   * A fix to get a function like imagecopymerge with alpha blending.
   *
   * Main script by aiden dot mail at freemail dot hu.
   * Transformed to imagecopymerge_alpha() by rodrigo dot polo at gmail dot com.
   * 
   * @param resource $dst_im 
   *   Destination image link resource.
   * @param resource $src_im
   *   Source image link resource.
   * @param int $dst_x
   *   x-coordinate of destination point.
   * @param int $dst_y
   *   y-coordinate of destination point.
   * @param int $src_x
   *   x-coordinate of source point.
   * @param int $src_y
   *   y-coordinate of source point.
   * @param int $src_w
   *   Source width.
   * @param int $src_h
   *   Source height.
   * @param int $pct
   *   Opacity in percent * 100.
   * @param int $minalpha
   *   Minimum alpha value in the image resource.
   * 
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   * 
   * @see http://uk3.php.net/manual/en/function.imagecopymerge.php#88456
   */
  protected static function imagecopymergeAlpha(&$dst_im, &$src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $minalpha = NULL) {
    if (!isset($pct)) {
      return FALSE;
    }
    $pct /= 100;
    // Get image width and height.
    $w = imagesx($src_im);
    $h = imagesy($src_im);
    // Turn alpha blending off.
    imagealphablending($src_im, FALSE);
    if (is_null($minalpha)) {
      // Find the most opaque pixel in the image (smallest alpha value).
      $minalpha = 127;
      for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
          $alpha = (imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
          if ($alpha < $minalpha) {
            $minalpha = $alpha;
          }
        }
      }
    }
    // Loop through image pixels and modify alpha for each.
    for ($x = $src_x; $x < $src_x + $src_w; $x++) {
      for ($y = $src_y; $y < $src_y + $src_h; $y++) {
        // Get current alpha value (represents the transparency).
        $colorxy = imagecolorat($src_im, $x, $y);
        $alpha = ($colorxy >> 24) & 0xFF;
        // Calculate new alpha.
        if ($minalpha !== 127) {
          $alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
        }
        else {
          $alpha += 127 * $pct;
        }
        // Get the color index with new alpha.
        $alphacolorxy = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
        // Set pixel with the new color + opacity.
        if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
          return FALSE;
        }
      }
    }
    // The image copy.
    imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
  }

  /**
   * Compute the minimum alpha value of the pixels of a given image resource.
   *
   * Warning, this function is very expensive.
   * 
   * @param resource $src_im
   *   Source image link resource.
   * 
   * @return int
   *   Minimum alpha value in the image resource.
   */
  protected static function alphaGetMin($src_im) {
    $w = imagesx($src_im);
    $h = imagesy($src_im);
    // Turn alpha blending off.
    imagealphablending($src_im, FALSE);
    // Find the most opaque pixel in the image (smallest alpha value).
    $minalpha = 127;
    for ($x = 0; $x < $w; $x++) {
      for ($y = 0; $y < $h; $y++) {
        $alpha = (imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
        if ($alpha < $minalpha) {
          $minalpha = $alpha;
        }
      }
    }
    return $minalpha;
  }
}
