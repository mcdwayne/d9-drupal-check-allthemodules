<?php

namespace Drupal\adsense;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for the adsense ad plugins.
 */
interface AdsenseAdInterface extends PluginInspectionInterface {

  /**
   * Creates the ad object, as specified by the definitions in the parameter.
   *
   * @param array $args
   *   Definitions of the ad object as per the AdsenseAd annotation.
   *
   * @return AdsenseAdBase
   *   Created ad object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Exception thrown in the event of problems with the plugin.
   */
  public static function createAd(array $args);

  /**
   * Return the ad content.
   *
   * @return array
   *   ad content
   */
  public function getAdContent();

  /**
   * Return the ad placeholder.
   *
   * @return array
   *   ad placeholder
   */
  public function getAdPlaceholder();

  /**
   * Return the list of available languages.
   *
   * @return array
   *   list of language options with the key used by Google and description.
   */
  public static function adsenseLanguages();

  /**
   * This is the array that holds all ad formats.
   *
   * All it has is a multi-dimensional array indexed by a key, containing the ad
   * type and the description.
   *
   * To add a new code:
   * - Make sure the key is not in use by a different format
   * - Go to Google AdSense
   *   . Get the dimensions
   *   . Get the description
   *
   * @param string $key
   *   Ad key for which the format is needed (optional).
   *
   * @return array
   *   if no key is provided: array of supported ad formats as an array (type,
   *   description).
   *   if a key is provided, the array containing the ad format for that key,
   *   or NULL if there is no ad with that key.
   */
  public static function adsenseAdFormats($key = NULL);

}
