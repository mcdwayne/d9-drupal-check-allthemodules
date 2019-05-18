<?php

/**
 * @file
 * Contains Drupal\google_adwords\GoogleAdwordsTracker.
 */

namespace Drupal\google_adwords;


/**
 * Class GoogleAdwordsTracker.
 *
 * @note This is empty now, as it is just a place to put common tracking code.
 *
 * @package Drupal\google_adwords
 */
class GoogleAdwordsTracker {

  /**
   * $var array $trackings
   *
   * @TODO make this a datatype
   */
  static protected $trackings;

  /**
   * Constructor.
   */
  public function __construct() {

    if (!is_array(self::$trackings)) {
      static::$trackings = [];
    }

  }

  /**
   * Register an AdWord Tracking
   *
   * @param $conversion_id
   * @param null $label
   * @param null $value
   * @param null $language
   * @param null $color
   * @param null $format
   * @returns null
   */
  public function addTracking($conversion_id, $label = NULL, $value = NULL, $language = NULL, $color = NULL, $format = NULL) {

    /**
     * @todo get these default values from the global settings
     */
    $label = ($label) ? $label : base64_encode($conversion_id);
    $language = ($language) ? $language : 'en';
    $color = ($color) ? $color : 'FFFFFF';
    $format = ($format) ? $format : 1;

    // add this tracking to our array
    static::$trackings[$conversion_id] = array(
      'conversion_id' => $conversion_id,
      'label' => $label,
      'language' => $language,
      'color' => $color,
      'format' => $format,
      'value' => $value,
    );
  }

  /**
   * Have any trackings been registered?
   *
   * @returns Boolean on whether or not trackings have been registered
   */
  public function hasTracking() {
    return (count(static::$trackings)>0);
  }

  /**
   * Add tracking JS to a render element
   *
   * @param array $element
   *   Render element onto which #js will be attached
   * @param Boolean $empty
   *   Empty tracking list after attaching?
   * @throws \Exception
   *   If a non-array is passed as an argument
   * @returns null
   */
  public function attachTrackingToElement(&$element, $empty = TRUE) {
    if (!static::hasTracking()) {
      return;
    }

    if (!is_array($element)) {
      /**
       * @todo create custom exception for this
       */
      throw new \Exception('Non render array passed for GoogleAdwords Tracking JS attachments');
    }

    /**
     * @var \Drupal\Core\Config\ImmutableConfig $config
     *   saved settings for google_adwords
     */
    $config = \Drupal::config('google_adwords.settings');

    // make sure that our adwords js is loaded ( @see google_adwords.libraries.yml )
    // #page won't accept JS anymore : element['#attached']['js']['external-google-adwords-script'] = $config->get('external_script'); // 'https://www.googleadservices.com/pagead/conversion.js';
    $element['#attached']['library'][] = 'google_adwords/google_adwords.tracker';
    $element['#attached']['library'][] = 'node/drupal.node.admin';

    // Add our JS values array
    $element['#attached']['drupalSettings']['google_adwords'] = [
      'defaults' => [
        'conversion_id' => $config->get('conversion_id'),
        'label' => $config->get('label'),
        'language' => $config->get('language'),
        'color' => $config->get('color'),
        'format' => $config->get('format'),
      ],
      'trackings' => static::$trackings
    ];

    // empty our trackings in case this
    if ($empty) {
      static::$trackings = [];
    }

  }
}