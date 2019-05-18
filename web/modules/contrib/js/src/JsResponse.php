<?php

namespace Drupal\js;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * JsResponse.
 */
class JsResponse extends AjaxResponse {

  use DependencySerializationTrait {
    __sleep as __traitSleep;
    __wakeup as __traitWakeup;
  }
  use StringTranslationTrait;

  /**
   * JS Callback service.
   *
   * @var \Drupal\js\Js
   */
  protected $js;

  /**
   * The content mime type.
   *
   * @var string
   */
  protected $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  public function __construct($data = NULL, $status = 200, array $headers = []) {
    parent::__construct($data, $status, $headers);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $keys = $this->__traitSleep();
    // If a response is serialized, that means the content has already been
    // constructed from the data. Because the data may contain objects that
    // aren't serializable (way down in the render array), then it should just
    // be removed entirely to avoid any container serializations.
    return array_diff($keys, ['data']);
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    $this->__traitWakeup();
    $this->data = Json::decode($this->content);
  }

  /**
   * Retrieves the data set by the callback.
   *
   * @return mixed
   *   The data set by the callback.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Retrieves the mime type of the response.
   *
   * @return string
   *   The mime type.
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * Retrieves a header value by name.
   *
   * @param string $name
   *   The header to retrieve.
   * @param mixed $default
   *   The default value if header is not set.
   * @param bool $first
   *   Flag indicating whether to return only the first value or all values for
   *   the header, TRUE by default.
   *
   * @return string|array
   *   The first header value if $first is true, an array of values otherwise.
   */
  public function getHeader($name, $default = NULL, $first = TRUE) {
    return $this->headers->get($name, $default, $first);
  }

  /**
   * Helper method to determine if an array is renderable.
   *
   * @param array $data
   *   The array to check.
   * @param bool $recursive
   *   If TRUE, the entire array (children) is searched, otherwise only the
   *   first level is checked.
   *
   * @return bool
   *   TRUE if renderable, FALSE if not.
   */
  function isRenderable($data = [], $recursive = TRUE) {
    // Immediately return if not an array.
    if (!is_array($data)) {
      return FALSE;
    }
    static $keys = [
      '#type', '#theme', '#theme_wrappers', '#children', '#markup',
      '#pre_render', '#post_render', '#lazy_builder', '#attached',
    ];
    if (array_intersect($keys, Element::properties($data))) {
      return TRUE;
    }
    if ($recursive) {
      // Cannot use \Drupal\Core\Render\Element::children here since that can
      // potentially trigger E_USER_ERROR if the array is invalid. Instead,
      // just filter the array by checking if the key is a "child" key.
      $children = array_filter(array_keys($data), '\Drupal\Core\Render\Element::child');
      foreach ($children as $child) {
        if (is_array($data[$child]) && $this->isRenderable($data[$child])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Processes the content for output.
   *
   * If content is a render array, it may contain attached assets to be
   * processed.
   *
   * @param array|string $data
   *   The data to be rendered, passed by reference.
   *
   * @return string
   *   HTML rendered content.
   */
  protected function render(&$data = []) {
    // Immediately return if data is not renderable.
    if (!$this->isRenderable($data)) {
      return $data;
    }

    return trim(\Drupal::service('renderer')->renderRoot($data));
  }

  /**
   * {@inheritdoc}
   *
   * @return $this
   */
  public function setContent($content) {
    if (is_array($content) || $content instanceof \ArrayObject) {
      $json = $content;

      if ($this->js) {
        // Set renderable data as "content".
        if (!$this->isRedirection() && $this->isRenderable($content)) {
//          // GET requests must be cached by query arguments.
//          if ($this->js->getRequest()->getMethod() === 'GET') {
//            $content['#cache']['context'][] = 'url.query_args';
//          }
          $json = ['content' => $this->render($content)];
        }

        // Include general Drupal information on non-redirecting requests.
        if (!$this->isRedirection()) {
          $json['title'] = $this->js->getCallback()->call('getTitle');
          $json['messages'] = drupal_get_messages();
        }

        // Add a reliable response parameter.
        $json['response']['code'] = $this->getStatusCode();
        $json['response']['message'] = isset(static::$statusTexts[$json['response']['code']]) ? static::$statusTexts[$json['response']['code']] : $this->t('Unknown');
      }

      $this->headers->remove('Content-Encoding');
      $content = Json::encode($json);
    }
    parent::setContent($content);
    return $this;
  }

  /**
   * Sets the data returned from a callback.
   *
   * @param mixed $data
   *   The data to set.
   *
   * @return $this
   */

  public function setData($data = []) {
    $this->data = $data;
    $this->setContent($this->data);
    return $this;
  }

  /**
   * Sets an header by name, explicitly.
   *
   * @param string $name
   *   The name of the header.
   * @param string|array $value
   *   The value or an array of values to set.
   * @param bool $replace
   *   Flag indicating whether to replace the value or not, TRUE by default.
   *
   * @return $this
   */
  public function setHeader($name, $value, $replace = TRUE) {
    $this->headers->set($name, $value, $replace);
    return $this;
  }

  /**
   * Sets the JS Callback Handler instance.
   *
   * @param \Drupal\js\Js $js
   *
   * @return $this
   */
  public function setJs(Js $js) {
    $this->js = $js;
    return $this;
  }

}
