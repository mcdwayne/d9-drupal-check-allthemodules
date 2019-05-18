<?php

namespace Drupal\spin;

/**
 * Helper class.
 */
class SpinHelper {

  /**
   * Extract the spin options from the form.
   *
   * @param array $values
   *   The form values array.
   *
   * @return array
   *   An array of spin options.
   */
  public static function extractOptions(array $values) {
    $options = [];

    foreach ($values as $field => $val) {
      if (!preg_match('/^spin_([-\w]+)/', $field, $m)) {
        continue;
      }
      $options[$m[1]] = $val;
    }
    ksort($options);

    return $options;
  }

  /**
   * Fetch the serialized display option data.
   *
   * @param int $sid
   *   The spin ID.
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return string
   *   The serialized display option data.
   */
  public static function getData($sid, $type) {
    $defaults = self::getDataPrototype($type);

    if (empty($defaults)) {
      return [];
    }
    $data = SpinStorage::getData($sid);

    $data = $data ? unserialize($data) : [];

    return is_array($data) ? $data + $defaults : $defaults;
  }

  /**
   * Fetch the serialized display option data.
   *
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return string
   *   The serialized display option data.
   */
  public static function getDefaultData($type) {
    $defaults = self::getDataPrototype($type);

    if (empty($defaults)) {
      return [];
    }
    $data = SpinStorage::getDefaultData($type);

    $data = $data ? unserialize($data) : [];

    return is_array($data) ? $data + $defaults : $defaults;
  }

  /**
   * Build a string of data-options.
   *
   * @return string
   *   A string of Magic Slideshow data options.
   */
  public static function getSlideshowDataOptions(array $options = []) {
    $attribute = '';
    $options += self::getDataPrototype('slideshow');

    ksort($options);

    foreach ($options as $key => $val) {
      $attribute .= $attribute ? "; $key:$val" : "$key:$val";
    }
    return $attribute;
  }

  /**
   * Build a string of data-options.
   *
   * @return string
   *   A string of Magic Slideshow data options.
   */
  public static function getSpinQueryString(array $options = []) {
    $options += self::getDataPrototype('spin');
    $query = '';

    ksort($options);

    foreach ($options as $key => $val) {
      $query .= $query ? "&$key=$val" : "$key=$val";
    }
    return $query;
  }

  /**
   * Retrive the image URL.
   *
   * @param array $values
   *   The form values array.
   * @param array $options
   *   The profile options.
   *
   * @return Drupal\Core\Database\Query\InsertQuery|Update
   *   An InsertQuery or Update object for this connection.
   */
  public static function mergeSpin(array $values, array $options) {
    $profile = ($values['type'] == 'spin') ? self::getSpinQueryString($options) : self::getSlideshowDataOptions($options);

    if (!empty($values['sid'])) {
      $fields = [
        'label'   => $values['label'],
        'profile' => $profile,
        'data'    => serialize($options),
      ];
      return SpinStorage::updateSpin($values['sid'], $fields);
    }
    else {
      $fields = [
        'name'    => $values['name'],
        'type'    => $values['type'],
        'label'   => $values['label'],
        'profile' => $profile,
        'data'    => serialize($options),
      ];
      return SpinStorage::createSpin($fields);
    }
  }

  /**
   * Fetch an array of default display option data.
   *
   * @param string $type
   *   The spin profile type, ("slideshow" or "spin").
   *
   * @return string
   *   An array of default display option data.
   */
  protected static function getDataPrototype($type = '') {
    switch ($type) {
      case 'slideshow':
        return [
          'arrows'          => 'true',
          'autoplay'        => 'false',
          'caption'         => 'false',
          'caption-effect'  => 'fade',
          'effect'          => 'blocks',
          'effect-speed'    => '600',
          'fullscreen'      => 'true',
          'keyboard'        => 'false',
          'links'           => 'false',
          'loader'          => 'true',
          'loop'            => 'false',
          'orientation'     => 'horizontal',
          'pause'           => 'false',
          'preload'         => 'false',
          'selectors'       => 'bottom',
          'selectors-eye'   => 'true',
          'selectors-style' => 'thumbnails',
          'shuffle'         => 'false',
        ];

      case 'spin':
        return [
          'autospin'          => 'once',
          'autospinSpeed'     => '5000',
          'autospinStart'     => 'load',
          'autospinStop'      => 'click',
          'autospinDirection' => 'clockwise',
          'spin'              => 'drag',
          'speed'             => '50',
          'zoom'              => '3',
          'rightClick'        => 'false',
          'fullscreen'        => 'true',
          'initializeOn'      => 'load',
        ];
    }
    return [];
  }

}
