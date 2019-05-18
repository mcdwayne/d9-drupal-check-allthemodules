<?php

namespace Drupal\image_tools\Commands;

use Drush\Commands\DrushCommands;
use Drupal\image_tools\Services\ImageService;

/**
 * Drush Command to handle image handling.
 */
class ImageToolsCommand extends DrushCommands {

  /**
   * ImageService.
   *
   * @var \Drupal\image_tools\Services\ImageService
   */
  private $imageService;

  /**
   * DrushImageCommand constructor.
   *
   * @param \Drupal\image_tools\Services\ImageService $imageService
   *   ImageService.
   */
  public function __construct(ImageService $imageService) {
    parent::__construct();
    $this->imageService = $imageService;
  }

  /**
   * Convert all Images with type png to jpg.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option dry_run
   *   Display images which should be converted. No image will be modified.
   *
   * @command image:convertPngToJpeg
   * @aliases i:cptj
   */
  public function convertPngToJpeg(array $options = ['dry_run' => FALSE]) {
    $files = $this->imageService->loadPngImages();

    if ($options['dry_run']) {
      foreach ($files as $fid => $element) {
        drush_print($fid . " | " . basename($element['path']) . ($element['transparency'] ? " | has transparency (alpha channel)." : ""));
      }

      return;
    }

    list($images_converted, $current_size, $new_size, $saved_size) = $this->imageService->convertPngImagesToJpeg($files);

    $this->logger()->success("Converted $images_converted images from png to jpg. We had $current_size MB and reduced it to $new_size MB. We saved $saved_size MB.");
  }

  /**
   * Resize images to a given max width. Default 2048.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option dry_run
   *   Display images which should be converted. No image will be modified.
   * @option max_width
   *   The max width for the images. Images larger then max_width getting  resized. Default 2048
   * @option include_png
   *   Also includes PNG images. With transparency the background will be white.
   *
   * @command image:resize
   * @aliases i:r
   */
  public function resizeImages(array $options = [
    'dry_run' => FALSE,
    'max_width' => ImageService::IMAGE_TOOLS_DEFAULT_MAX_WIDTH,
    'include_png' => FALSE,
  ]
  ) {
    $files = $this->imageService->findLargeWidthImages($options['max_width'], $options['include_png']);

    if ($options['dry_run']) {
      foreach ($files as $fid => $element) {
        drush_print($fid . " | " . basename($element['path']) . (isset($element['transparency']) && $element['transparency'] ? " | has transparency (alpha channel)." : ""));
      }

      return;
    }

    list($images_converted, $current_size, $new_size) = $this->imageService->resizeImages($files, $options['max_width']);

    $this->logger()->success(dt("Resized $images_converted images to the an max width of " . $options['max_width'] . " pixels. We had $current_size MB, now we need $new_size MB."));
  }

  /**
   * Create Test Images.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @option amount
   *   The amount of images to create. Default: 1000
   * @option width
   *   The width of images. Default: 2100
   *
   * @command image:create:demo
   * @aliases i:cd
   */
  public function createDemoImages(array $options = ['amount' => 1000, 'width' => 2100]) {
    $directory = 'public://' . date("Y-m") . '/';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);

    for ($i = 0; $i < $options['amount']; $i++) {
      $image = $this->createImage($options['width'], round(rand($options['width'] - 100, $options['width'] + 100) * 0.75));

      $file = file_save_data(file_get_contents($image), $directory . basename($image), FILE_EXISTS_REPLACE);
      $file->setOwnerId(1);
      $file->save();

      unlink($image);
    }

    $this->logger()->success("created " . $options['amount'] . " images.");
  }

  /**
   * Create Demo Image with random text and colors.
   *
   * @param int $width
   *   Width.
   * @param int $height
   *   Height.
   *
   * @return string
   */
  private function createImage($width, $height) {
    $file = $this->generateRandomString();
    $filename = sys_get_temp_dir() . "/" . $file . ".png";
    $im = imagecreate($width, $height);
    imagecolorallocate($im, 255, 255, 255);
    $text_color = imagecolorallocate($im, rand(1, 254), rand(1, 254), rand(1, 254));
    imagestring($im, rand(3, 5), rand(10, 200), rand(10, 200), $file, $text_color);
    imagepng($im, $filename);
    imagedestroy($im);

    return $filename;
  }

  /**
   * Generate Random Text String.
   *
   * @param int $length
   *   Length.
   *
   * @return string
   */
  private function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

}
