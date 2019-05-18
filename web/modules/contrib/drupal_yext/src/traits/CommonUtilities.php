<?php

namespace Drupal\drupal_yext\traits;

use Drupal\drupal_yext\Yext\FieldMapper;
use Drupal\drupal_yext\Yext\Yext;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
// @codingStandardsIgnoreStart
use Drupal\node\NodeTypeInterface;
// @codingStandardsIgnoreEnd
use Drupal\node\Entity\NodeType;

/**
 * General utilities trait.
 *
 * If your class needs to use any of these, add "use CommonUtilities" your class
 * and these methods will be available and mockable in tests.
 */
trait CommonUtilities {

  /**
   * Get an arbitrary element from an nested associative array.
   *
   * @param array $array
   *   An associative array such as [a => [b => c]].
   * @param string $type
   *   The expected type of c.
   * @param array $keys
   *   The path to the data we want, for example [a, b].
   * @param mixed $default
   *   The default value in case there is no available data. This should
   *   be the same type as $type, and will be ignored if $required is
   *   TRUE.
   * @param bool $required
   *   Whether or not the data is required to be present and non-empty.
   * @param string $required_message
   *   A message to add to the exception in case the data is not
   *   present but required.
   * @param array $options
   *   Can contain cast-as-type (TRUE or FALSE, FALSE being default).
   *
   * @return mixed
   *   Data of type $type.
   *
   * @throws \Throwable
   */
  public function assocArrayElem(array $array, string $type, array $keys, $default, bool $required = FALSE, string $required_message = '', array $options = []) {
    $default_type = gettype($default);
    if ($default_type != $type) {
      throw new \Exception('Default value type is ' . $default_type . ', not ' . $type);
    }
    $structure = $array;
    foreach ($keys as $key) {
      if (!is_array($structure)) {
        throw new \Exception('We are expecting ' . $key . ' to be an array, it is ' . gettype($structure));
      }
      if (empty($structure[$key])) {
        if ($required) {
          throw new \Exception('Required keys ' . implode(', ', $keys) . ' not present in array. ' . $required_message);
        }
        $structure = $default;
        break;
      }
      $structure = $structure[$key];
    }
    $mytype = gettype($structure);
    if ($mytype != $type) {
      if (!empty($options['cast-as-type'])) {
        $structure = settype($structure, $type);
      }
      else {
        throw new \Exception('The return structure is ' . $mytype . ', not ' . $type);
      }
    }
    return $structure;
  }

  /**
   * Checks to make sure a server is available.
   *
   * @param string $server
   *   A server such as http://drupal.
   * @param string $message
   *   Will be filled with the error message if there is one.
   *
   * @return bool
   *   Whether the server is accessible.
   */
  public function checkServer(string $server, string &$message = '') : bool {
    try {
      $response = serialize($this->httpGet($server));
      $message = 'Call to server resulted in ' . $response;
      return TRUE;
    }
    catch (\Throwable $t) {
      $message = $t->getMessage();
      $this->watchdogThrowable($t);
      return FALSE;
    }
  }

  /**
   * Mockable wrapper around \Drupal::config()->get().
   */
  public function configGet($variable, $default = NULL) {
    $return = \Drupal::config('drupal_yext.general.settings')->get($variable, $default);
    return ($return === NULL) ? $default : $return;
  }

  /**
   * Mockable wrapper around setting a config item.
   */
  public function configSet($variable, $value) {
    $config = \Drupal::service('config.factory')->getEditable('drupal_yext.general.settings');
    $config->set($variable, $value)->save();
  }

  /**
   * Mockable wrapper around date().
   */
  public function date(string $format, $timestamp = NULL) : string {
    if ($timestamp === NULL) {
      return date($format);
    }
    else {
      return date($format, $timestamp);
    }
  }

  /**
   * Mockable wrapper around drupal_set_message().
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    return drupal_set_message($message, $type, $repeat);
  }

  /**
   * Wrap \Drupal::service('entity_field.manager')->getFieldDefinitions().
   */
  public function fieldDefinitions(string $entity_type, string $bundle) {
    $entityManager = \Drupal::service('entity_field.manager');
    return $entityManager->getFieldDefinitions($entity_type, $bundle);
  }

  /**
   * Get the field mapping singleton.
   *
   * @return FieldMapper
   *   The field mapper singleton.
   */
  public function fieldmap() : FieldMapper {
    return FieldMapper::instance();
  }

  /**
   * Mockable wrapper around \Drupal::httpClient()->get().
   */
  public function httpGet($uri, $options = []) {
    $this->watchdog('Making request to ' . $uri . ' with the following options:');
    $this->watchdog(serialize($options));
    return \Drupal::httpClient()->get($uri, $options);
  }

  /**
   * Get an image from the web and save it in a file.
   *
   * This code is partially based on code in the stage_file_proxy module.
   * See also
   *  http://realityloop.com/blog/2015/10/08/programmatically-attach-files-node-drupal-8
   *
   * @param string $url
   *   An URL which contains an image.
   * @param Entity $entity
   *   A Drupal entity.
   * @param string $field_name
   *   An image field on the entity.
   *
   * @throws Exception
   */
  public function imageFromWebToField(string $url, Entity $entity, string $field_name) {
    $response = $this->httpGet($url);
    $response_code = $response->getStatusCode();
    if ($response_code != 200) {
      throw new \Exception($url . ' results in response code ' . $response_code);
    }
    $response_headers = $response->getHeaders();
    $header_length = array_shift($response_headers['Content-Length']);
    $header_type = array_shift($response_headers['Content-Type']);
    $content = $response->getBody()->getContents();
    $content_length = strlen($content);
    if (!in_array($header_type, ['image/jpeg', 'image/png'])) {
      throw new \Exception($header_type . ' is unrecognized.');
    }
    if ($header_length != $content_length) {
      throw new \Exception('Possible incomplete download: ' . $content_length . ' bytes downloaded, ' . $header_length . ' expected.');
    }

    $file = file_save_data($content, 'public://' . $entity->uuid() . '.' . str_replace('image/', '', $header_type), FILE_EXISTS_REPLACE);

    $entity->set($field_name, $file);
  }

  /**
   * Get a link with text and a path.
   *
   * This is relatively equivalent to the l() function in Drupal 7.
   *
   * @return Link
   *   A displayable link.
   *
   * @throws Exception
   */
  public function link(string $text, string $path) : Link {
    return Link::fromTextAndUrl($text, Url::fromUri($path));
  }

  /**
   * Wrapper around NodeType::load() which throws exception if no such type.
   */
  public function nodeTypeLoad(string $type) : NodeTypeInterface {
    $return = NodeType::load($type);
    if (!$return) {
      throw new \Exception($this->t('Could not load node type @t.', [
        '@t' => $type,
      ]));
    }
    return $return;
  }

  /**
   * Mockable wrapper around \Drupal::state()->get().
   */
  public function stateGet($variable, $default = NULL) {
    return \Drupal::state()->get($variable, $default);
  }

  /**
   * Mockable wrapper around \Drupal::state()->set().
   */
  public function stateSet($variable, $value) {
    \Drupal::state()->set($variable, $value);
  }

  /**
   * Mockable wrapper around t().
   *
   * See that function for details.
   */
  public function t($string, array $args = [], array $options = []) {
    // @codingStandardsIgnoreStart
    return t($string, $args, $options);
    // @codingStandardsIgnoreEnd
  }

  /**
   * Log a string to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdog(string $string) {
    \Drupal::logger('drupal_yext')->notice($string);
  }

  /**
   * Log an error to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdogError(string $string) {
    \Drupal::logger('drupal_yext')->error($string);
  }

  /**
   * Log a \Throwable to the watchdog.
   *
   * @param \Throwable $t
   *   A \throwable.
   */
  public function watchdogThrowable(\Throwable $t, $message = NULL, $variables = array(), $severity = RfcLogLevel::ERROR, $link = NULL) {

    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($t);

    \Drupal::logger('drupal_yext')->log($severity, $message, $variables);
  }

  /**
   * Get the Yext app singleton.
   *
   * @return Yext
   *   The Yext singleton.
   */
  public function yext() {
    return Yext::instance();
  }

  /**
   * The Drupal node type which will be populated by Yext data.
   *
   * @return string
   *   A node type such as 'article'.
   *
   * @throws \Throwable
   */
  public function yextNodeType() : string {
    return $this->configGet('target_node_type', 'article');
  }

}
