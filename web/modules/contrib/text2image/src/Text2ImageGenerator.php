<?php

namespace Drupal\text2image;

/**
 * Class Text2ImageGenerator.
 */
class Text2ImageGenerator {

  protected $settings;
  protected $imageFactory;
  protected $imagePath;

  /**
   * Constructs a new Text2ImageGenerator object.
   */
  public function __construct() {
    $this->settings = [
      'width' => 220,
      'height' => 220,
      'bg_color' => '#ffffff',
      'fg_color' => '#000000',
      'font_file' => '',
      'font_size' => '20',
    ];
    $this->imagePath = 'public://text2image/';
    $this->imageFactory = \Drupal::service('image.factory');
  }

  /**
   * Initialise this object with settings.
   *
   * @param array $settings
   *   Array of key value pairs.
   *
   * @return object
   *   Return this instance of Text2ImageGenerator.
   */
  public function init(array $settings = []) {
    $this->settings = array_merge($this->settings, $settings);
    return $this;
  }

  /**
   * Set the path to output image files.
   *
   * @param string $path
   *   Stream wrapper for output image files, e.g. public://text2image/.
   *
   * @return object
   *   Return this instance of Text2ImageGenerator.
   */
  public function setImagePath($path) {
    $this->imagePath = $path;
    return $this;
  }

  /**
   * Get the path to output image files.
   *
   * @return string
   *   Image path.
   */
  public function getImagePath() {
    return $this->imagePath;
  }

  /**
   * Return a value for given key from settings.
   *
   * @param string $key
   *   Key name.
   *
   * @return string
   *   Key value.
   */
  public function getSetting($key) {
    if (isset($this->settings[$key])) {
      return $this->settings[$key];
    }
    return '';
  }

  /**
   * Prepare input text.
   *
   * @param string $text
   *   Text string.
   *
   * @return string
   *   Prepared text.
   */
  public function prepareText($text) {
    $text_encoding = mb_detect_encoding($text, 'UTF-8, ISO-8859-1');
    if ($text_encoding !== 'UTF-8') {
      $text = mb_convert_encoding($text, 'UTF-8', $text_encoding);
    }
    return $text;
  }

  /**
   * Generate an image toolkit resource.
   *
   * @param string $text
   *   Text string.
   *
   * @return resource
   *   Image resource.
   */
  public function generateResource($text) {
    if (!$bg_rgb = $this->hex2rgba($this->getSetting('bg_color'))) {
      $bg_rgb = [rand(0, 255), rand(0, 255), rand(0, 255)];
    }
    if (!$fg_rgb = $this->hex2rgba($this->getSetting('fg_color'))) {
      $fg_rgb = [255 - $bg_rgb[0], 255 - $bg_rgb[1], 255 - $bg_rgb[2]];
    }
    $img_width = $this->getSetting('width');
    $img_height = $this->getSetting('height');
    $img_half_height = $img_height / 2;
    $img_half_width = $img_width / 2;
    $margin_left = intval($img_width * 0.15);
    $margin_top = intval($img_height * 0.15);
    $font_file = $this->getSetting('font_file');
    $font_size = $this->getSetting('font_size');
    $font_angle = 0;

    $img = imagecreatetruecolor($img_width, $img_height);
    $bg_trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $bg_trans);
    $bg_color = imagecolorallocate($img, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2]);
    imagefill($img, 0, 0, $bg_color);
    $fg_color = imagecolorallocate($img, $fg_rgb[0], $fg_rgb[1], $fg_rgb[2]);

    $text_bbox = imagettfbbox($font_size, $font_angle, $font_file, $text);
    $text_length_chars = strlen($text);
    $text_length_pixels = $text_bbox[4];
    $font_width = $text_length_pixels / $text_length_chars;
    $line_length_max = intval(($img_width - ($margin_left * 2)) / $font_width);

    $text_wrapped = wordwrap($text, $line_length_max, PHP_EOL);
    $chunks = explode(PHP_EOL, $text_wrapped);
    $text_window_height = intval($img_height - ($margin_top * 2));
    $line_height = intval($text_window_height / count($chunks));
    if ($line_height == $text_window_height) {
      $y = $img_half_height;
    }
    elseif ($line_height == ($text_window_height / 2)) {
      $y = ($img_half_height / 2) + $margin_top;
    }
    else {
      $y = $font_size + $margin_top;
    }

    foreach ($chunks as $chunk) {
      $chunk_bbox = imagettfbbox($font_size, $font_angle, $font_file, $chunk);
      $chunk_half_width = ($chunk_bbox[4] - $chunk_bbox[6]) / 2;
      $x = $img_half_width - $chunk_half_width;
      imagettftext($img, $font_size, $font_angle, $x, $y, $fg_color, $font_file, $chunk);
      $y += $line_height;
    }
    imagealphablending($img, TRUE);
    imagesavealpha($img, TRUE);
    return $img;
  }

  /**
   * Get a previously generated image or generate a new image.
   *
   * @param string $text
   *   Text string.
   * @param bool $replace
   *   TRUE to replace existing file.
   *
   * @return object
   *   Image object.
   */
  public function getImage($text, $replace = FALSE) {
    $text = $this->prepareText($text);
    $uri = $this->createFilename($text . '.png');
    $url = file_create_url($uri);
    if ($replace || !file_exists($url)) {
      $res = $this->generateResource($text);
      $image = $this->imageFactory->get(NULL, 'gd');
      $image->getToolkit()->setResource($res)->setType(IMAGETYPE_PNG);
      $uri = $this->save($image, $text, $replace);
    }
    else {
      $image = $this->imageFactory->get($url, 'gd');
    }
    $image->width = $image->getWidth();
    $image->height = $image->getHeight();
    $image->title = $text;
    $image->alt = $text;
    $image->entity = FALSE;
    $image->uri = $uri;
    return $image;
  }

  /**
   * Save image to destination.
   *
   * @param object $image
   *   Object of type Image.
   * @param string $name
   *   Text string.
   * @param bool $replace
   *   TRUE to unlink existing file.
   *
   * @return mixed
   *   URI | FALSE
   */
  public function save($image, $name, $replace = FALSE) {
    if (!$image->isValid()) {
      return FALSE;
    }
    if (!file_prepare_directory($this->imagePath, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      \Drupal::logger('text2image')->error('Failed to create directory: %path', ['%path' => $this->imagePath]);
      return FALSE;
    }
    $uri = $this->createFilename($name . '.png');
    if ($replace && file_exists($uri)) {
      file_unmanaged_delete($uri);
    }
    if (!$image->save($uri)) {
      \Drupal::logger('text2image')->error('Failed to save Text2Image file: %uri .', ['%uri' => $uri]);
      return FALSE;
    }
    return $uri;
  }

  /**
   * Compile a system-safe filename for an image.
   *
   * @param string $basename
   *   Text string.
   *
   * @return string
   *   Filename with full image path.
   */
  public function createFilename($basename) {
    $basename = preg_replace('/[\x00-\x1F]/u', '_', $basename);
    if (substr(PHP_OS, 0, 3) == 'WIN') {
      $basename = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $basename);
    }
    return $this->imagePath . $basename;
  }

  /**
   * Convert hex color string to rgb array.
   *
   * @param string $color
   *   Text string.
   *
   * @return array
   *   Array of string values for keys 0=red,1=green,2=blue.
   */
  public function hex2rgba($color) {
    if (empty($color)) {
      return FALSE;
    }
    if ($color[0] == '#') {
      $color = substr($color, 1);
    }
    if (strlen($color) == 6) {
      $hex = [
        $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5],
      ];
    }
    elseif (strlen($color) == 3) {
      $hex = [
        $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2],
      ];
    }
    else {
      return $default;
    }
    return array_map('hexdec', $hex);
  }

}
