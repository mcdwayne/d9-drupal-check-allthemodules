<?php

namespace Drupal\wsdata\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Wsdecoder plugin plugins.
 */
abstract class WSDecoderBase extends PluginBase implements WSDecoderInterface {
  /**
   * Storage for decoded data.
   *
   * @var mixed
   */
  public $data;

  /**
   * Storage for error information.
   *
   * @var string
   */
  protected $error;

  /**
   * Languages which we have data for.
   *
   * @var mixed
   */
  protected $languages = FALSE;

  /**
   * Returns an array of the content type of the data this processor accepts.
   */
  public function accepts() {
    return [
      'application/octet-stream',
    ];
  }

  /**
   * Decode the web service response string into an array.
   */
  abstract protected function decode($data);

  /**
   * {@inheritdoc}
   */
  public function __construct($data = NULL, &$entity = NULL, $lang = NULL) {
    $this->entity = $entity;
    if (isset($data) and $data) {
      $this->addData($data, $lang);
    }
  }

  /**
   * Retrieve error message, if any.
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Retrieve the value for the given data key.
   *
   * This function retrieves data from the structured array in $this->data
   *  using $key as a key.  $key should be a string, with the character ':'
   *  delimiting the parts of the key.
   *  I.E.  The key  something:someplace with retrive $this->data['foo']['bar']
   *  N.B.  This function can be overridden to work with whatever the ->decode
   *  function is implemented to return.
   *
   * @param string $key
   *   Optional - Data key to load.
   * @param string $lang
   *   Optional - Language key.
   *
   * @return mixed
   *   Returns the requested data, FALSE otherwise.
   */
  public function getData($key = NULL, $lang = NULL) {
    if (!is_array($this->data)) {
      return $this->data;
    }

    $return_data = FALSE;
    // Paths to load data from.
    $paths = [];

    // First, see if we want a specific language.
    if ($this->languages) {
      if (!is_null($lang) and array_key_exists($lang, $this->data)) {
        $paths[$lang] = !empty($key) ? $lang . ':' . $key : $lang;
      }
      else {
        foreach ($this->languages as $lang) {
          $paths[$lang] = !empty($key) ? $lang . ':' . $key : $lang;
        }
      }
    }
    else {
      if (!empty($key)) {
        $paths[$key] = $key;
      }
    }

    // Get the raw data.
    $return_data = $this->data;

    // Simplest case, return all data.
    if (empty($paths)) {
      return $return_data;
    }

    // Second simplest case, one specific value.
    if (!empty($paths[$key])) {
      $location = explode(':', $paths[$key]);
      foreach ($location as $l) {
        if (isset($return_data[$l])) {
          $return_data = $return_data[$l];
        }
        else {
          $return_data = FALSE;
        }
      }
      return $return_data;
    }

    // Third case, one specific value in a given language.
    if (!empty($paths[$lang]) and count($paths) == 1) {
      $location = explode(':', $paths[$lang]);
      foreach ($location as $l) {
        if (isset($return_data[$l])) {
          $return_data = $return_data[$l];
        }
        else {
          $return_data = FALSE;
        }
      }
      // Language specific data is always keyed by the language.
      $return_data[$lang] = $return_data;
      return $return_data;
    }

    // Lastly, the complicated case. Keyed value for all languages.
    if ($this->languages and count($paths) > 1) {
      $keyed_data = [];
      foreach ($paths as $p => $path) {
        // Reset return data.
        $return_data = $this->data;
        $location = explode(':', $path);
        foreach ($location as $l) {
          if (isset($return_data[$l])) {
            $return_data = $return_data[$l];
          }
          else {
            $return_data = FALSE;
          }
        }
        $keyed_data[$p] = $return_data;
      }

      // Finally, put the keyed data back into the return data.
      return $keyed_data;
    }
  }

  /**
   * Add data to an empty object or replace all existing data.
   *
   * In some cases, it may require multiple web service requests
   * to load language specific content. You can add each
   * request data result to the same processor object. getData()
   * should then return the merged data keyed by language.
   *
   * If your webservice returns all data for all languages in
   * a single request, leave $lang to NULL (not LANGUAGE_NONE).
   * LANGUAGE_NONE is considered a valid language and triggers
   * the language keying.
   *
   * @param mixed $data
   *   A set of data to decode.
   * @param string $lang
   *   Optional - Language key for the data being added.
   */
  public function addData($data, $lang = NULL, $context = []) {
    $this->context = $context;
    if (!is_null($lang) and !empty($data)) {
      $this->languages[$lang] = $lang;
      $this->data[$lang] = $this->decode($data);
    }
    else {
      // Default action, just decode the data.
      $this->data = $this->decode($data);
    }
  }

}
