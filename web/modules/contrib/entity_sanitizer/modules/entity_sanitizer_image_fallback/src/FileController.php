<?php

namespace Drupal\entity_sanitizer_image_fallback;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileController extends ControllerBase {

  /**
   * Generates a dummy image and transfers it to the browser.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $width
   *   The width of the generated image.
   * @param int $height
   *   The height of the generated image.
   * @param int $filetype
   *   The filetype of the requested image.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The generated image as response.
   */
  public function generate(Request $request, $width, $height, $filetype) {
    // Create the image.
    $im = imagecreatetruecolor($width, $height);

    // Allocate the colors we use.
    $black = imagecolorallocate($im, 0, 0, 0);
    $gray = imagecolorallocate($im, 228, 228, 228);

    imagefill($im, 0, 0, $gray);

    // Draw a border around our image.
    imagerectangle($im, 0, 0, $width - 1, $height - 1, $black);

    // Make our cross lines thicker.
    imagesetthickness($im, 2);

    // Draw a cross on the image.
    imageline($im, 0, 0, $width, $height, $black);
    imageline($im, $width, 0, 0, $height, $black);

    // A GD font size of 1-5.
    $fontsize = 5;

    // The string with our image dimensions.
    $string = $width . "x" . $height;

    // If we have freetype support we find the exact text dimensions.
    if (is_callable('imagettfbbox')) {
      // Calculate the boundingbox of our string.
      list(, , $string_width, $string_height, , , ,) = imagettfbbox($fontsize, 0, "Arial", $string);
    }
    // Otherwise we just estimate.
    else {
      $string_width = strlen($string) * imagefontwidth($fontsize);
      $string_height = imagefontheight($fontsize);
    }

    // Provide some padding around the text.
    $padding_horizontal = 0.05 * $width;
    $padding_vertical = 0.05 * $height;

    // Text coordinates.
    $text_left = ($width - $string_width) / 2;
    $text_top = ($height - $string_height) / 2;
    $text_right = $text_left + $string_width;
    $text_bottom = $text_top + $string_height;

    // We create a square behind the text.
    imagefilledrectangle($im, $text_left - $padding_horizontal, $text_top - $padding_vertical, $text_right + $padding_horizontal, $text_bottom + $padding_vertical, $gray);

    // And we draw the string in the square.
    imagestring($im, $fontsize, $text_left, $text_top, $string, $black);

    // The image functions send the image output directly to the browser so
    // must capture it to send it through the proper methods.
    ob_start();

    // We try to output whatever is request but default to JPEG if we don't
    // support what's requested.
    switch ($filetype) {
      case 'gif':
        imagegif($im);
        $content_type = "image/gif";
        break;

      case 'png':
        imagepng($im);
        $content_type = "image/png";
        break;

      case 'jpg':
      case 'jpeg':
      default:
        imagejpeg($im);
        $content_type = "image/jpeg";
        break;
    }

    $imagedata = ob_get_clean();

    $headers = [
      "Content-Type" => $content_type,
    ];

    return new Response($imagedata, 200, $headers);
  }

}