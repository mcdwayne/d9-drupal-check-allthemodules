<?php

namespace Drupal\stock_photo_search;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Providers an interface for embed providers.
 */
interface ProviderPluginInterface extends PluginInspectionInterface {

  /**
   * Check if the plugin is applicable to the user input.
   *
   * @param string $input
   *   User input to check if it's a URL for the given provider.
   *
   * @return bool
   *   If the plugin works for the given URL.
   */
  public static function isApplicable($input);

  /**
   * Check if the plugin is applicable to search.
   *
   * @param bool $token
   *   Token to check if it's a input search for the given provider.
   *
   * @return bool
   *   If the plugin works for the given token.
   */
  public static function isSearch($token);

  /**
   * Get the URL of the remote image.
   *
   * This is used to download the remote image and place it on the local file
   * system so that it can be rendered with image styles. This is only called if
   * no existing file is found for the image and should not be called
   * unnecessarily, as it might query APIs for image information.
   *
   * @return string
   *   The URL to the remote image file.
   */
  public function getRemoteImageUrl($input);

  /**
   * Get the URL to the local image.
   *
   * This method does not gartunee that the file will exist, only that it will
   * be the location of the image after the download image method has been
   * called.
   *
   * @return string
   *   The URI for the local image.
   */
  public function getLocalImageUri();

  /**
   * Download the remote image URL to the local URI.
   */
  public function downloadImage();

  /**
   * Render image.
   *
   * @param string $image_style
   *   The quality of the image to render.
   * @param string $link_url
   *   Where the image should be linked to.
   *
   * @return array
   *   A renderable array of an image.
   */
  public function renderImage($image_style, $link_url);

  /**
   * Get the ID of the image from user input.
   *
   * @param string $input
   *   Input a user would enter into a image field.
   *
   * @return string
   *   The ID in whatever format makes sense for the provider.
   */
  public static function getIdFromInput($input);

  /**
   * Get the name of the image.
   *
   * @return string
   *   A name to represent the image for the given plugin.
   */
  public function getName();

}
