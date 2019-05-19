<?php

namespace Drupal\tag1quo\Adapter\Form;

use Drupal\tag1quo\Adapter\Core\Core;
use Drupal\tag1quo\VersionedClass;

/**
 * Class FormState.
 *
 * @internal This class is subject to change.
 */
class FormState extends VersionedClass {

  /**
   * @var \Drupal\tag1quo\Adapter\Core\Core
   */
  protected $core;

  /**
   * @var array
   */
  protected $formState;

  /**
   * FormState constructor.
   *
   * @param array|object $form_state
   *   The form state array (or object in newer Drupal versions).
   */
  public function __construct(&$form_state) {
    $this->core = Core::create();
    $this->formState = $form_state;
  }

  /**
   * Creates a new versioned FormState object.
   *
   * @param array|object $form_state
   *   The form state array (or object in newer Drupal versions).
   *
   * @return static
   */
  public static function create(&$form_state) {
    return static::createVersionedInstance([&$form_state]);
  }

  /**
   * @param $name
   * @param string $message
   *
   * @return $this
   */
  public function setErrorByName($name, $message = '') {
    if (is_string($name) && strpos($name, '.') !== FALSE) {
      $name = explode('.', $name);
    }
    if (is_array($name)) {
      $name = implode('][', $name);
    }
    \form_set_error($name, $message);
    return $this;
  }

  /**
   * Returns the submitted form value for a specific key.
   *
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return $values[$key]. If $key is an array, each element
   *   of the array will be used as a nested key. If $key = array('foo', 'bar')
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key, or NULL.
   */
  public function &getValue($key, $default = NULL) {
    $exists = NULL;
    if (is_string($key) && strpos($key, '.') !== FALSE) {
      $key = explode('.', $key);
    }
    $value = &$this->core->getNestedValue($this->getValues(), (array) $key, $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * Returns the submitted and sanitized form values.
   *
   * @return array
   *   An associative array of values submitted to the form.
   */
  public function &getValues() {
    if (!isset($this->formState['values'])) {
      $this->formState['values'] = array();
    }
    return $this->formState['values'];
  }

}
