<?php

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Drupal\past\PastEventInterface;

/**
 * The main Event class for the Past Simpletest backend.
 */
class PastEventSimpletest implements PastEventInterface {
  public $event_id;
  public $module;
  public $machine_name;
  public $message;
  public $severity;
  public $timestamp;
  public $sessionId;
  public $referer;
  public $location;
  public $uid;

  protected $arguments;
  protected $child_events = [];

  protected static $event_id_counter = 1;

  public function __construct(array $values = []) {
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * Outputs debugging information for saving the event.
   */
  public function save() {
    // Prepare all arguments.
    foreach ($this->getArguments() as $argument) {
      $argument->ensureType();
    }

    // Create a pretty debug string to show on the Simpletest view
    $out  = 'Module: ' . $this->getModule() . chr(10);
    $out .= 'Machine name: ' . $this->getMachineName() . chr(10);
    $out .= 'Message: ' . $this->getMessage() . chr(10);

    // Attach all Arguments
    $out .= 'Arguments: ' . chr(10);
    foreach ($this->getArguments() as $name => $argument) {
      $out .= $this->formatArgument($name, $argument);
    }

    // Assign an event id.
    $this->event_id = self::$event_id_counter++;

    // Debug outputs are shown on the Simpletest frontend.
    debug($out);
  }

  /**
   * {@inheritdoc}
   */
  public function addArgument($key, $data, array $options = []) {
    if (!is_array($this->arguments)) {
      $this->arguments = [];
    }

    // If it is an object, clone it to avoid changing the original and log it
    // at the current state. Except when it can't, like e.g. exceptions.
    if (is_object($data) && !($data instanceof Exception)) {
      $data = clone $data;
    }

    // Special support for exceptions, convert them to something that can be
    // stored.
    if (isset($data) && $data instanceof Exception) {
      $data = Error::decodeException($data) + ['backtrace' => $data->getTraceAsString()];
    }

    // Remove values which were explicitly added to the exclude filter.
    if (!empty($options['exclude'])) {
      foreach ($options['exclude'] as $exclude) {
        if (is_array($data)) {
          unset($data[$exclude]);
        }
        elseif (is_object($data)) {
          unset($data->$exclude);
        }
      }
      unset($options['exclude']);
    }

    $this->arguments[$key] = new PastEventSimpletestArgument(['name' => $key, 'original_data' => $data] + $options);
    return $this->arguments[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function addArgumentArray($key_prefix, array $data, array $options = [], $delimiter = ':') {
    $arguments = [];
    foreach ($data as $key => $value) {
      $arguments[$key] = $this->addArgument($key_prefix . $delimiter . $key, $value, $options);
    }
    return $arguments;
  }

  /**
   * {@inheritdoc]}
   */
  protected function loadArguments() {
    if (!is_array($this->arguments)) {
      $this->arguments = [];
    }
  }

  /**
   * {@inheritdoc]}
   */
  public function getArgument($key) {
    $this->loadArguments();
    return isset($this->arguments[$key]) ? $this->arguments[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    $this->loadArguments();
    return $this->arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function addException(Exception $exception, array $options = [], $severity = RfcLogLevel::ERROR) {
    if (($this->getSeverity() == NULL) || ($this->getSeverity() > $severity)) {
      $this->setSeverity($severity);
    }
    $this->addArgument('exception', $exception, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return $this->severity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->event_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEventId($event_id) {
    $this->parent_event_id = $event_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverity($severity) {
    $this->severity = $severity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->sessionId;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($session_id) {
    $this->sessionId = $session_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferer() {
    return $this->referer;
  }

  /**
   * {@inheritdoc}
   */
  public function setReferer($referer) {
    $this->referer = $referer;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return $this->location;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location) {
    $this->location = $location;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    $this->uid = $uid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid() {
    return $this->uid;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($machine_name) {
    $this->machine_name = $machine_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setModule($module) {
    $this->module = $module;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addChildEvent($event_id) {
    $this->child_events[] = $event_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildEvents() {
    return $this->child_events;
  }

  /**
   * Formats an argument in HTML markup.
   *
   * @param string $name
   *   Name of the argument.
   * @param PastEventArgument $argument
   *   Argument instance.
   *
   * @return string
   *   A HTML div describing the argument and its data.
   */
  protected function formatArgument($name, $argument) {
    $back = '';
    $data = $argument->getData();
    if (is_array($data) || is_object($data)) {
      foreach ($data as $k => $v) {
        $back .= '   [' . strip_tags($k) . '] => (' . gettype($v) . ') ' . $this->parseObject($v) . chr(10);
      }
      $back = chr(10) . $back;
    }
    else {
      $back = (is_string($data) ? strip_tags($data) : $data) . chr(10);
    }
    $back = ' ' . strip_tags($name) . ' (' . gettype($data) . '): ' . $back;
    return $back;
  }

  /**
   * Formats an object in HTML markup.
   *
   * @param object $obj
   *   The value to be formatted. Any type is accepted.
   * @param int $recursive
   *   (optional) Recursion counter to avoid long HTML for deep structures.
   *   Should be unset for any calls from outside the function itself.
   *
   * @return string
   *   A HTML div describing the value.
   */
  protected function parseObject($obj, $recursive = 0) {
    $max_recursion = \Drupal::config('past.settings')->get('max_recursion');
    if ($recursive > $max_recursion) {
      return t('_Too many nested objects ( @recursion )_', ['@recursion' => $max_recursion]);
    }
    if (is_scalar($obj) || is_null($obj)) {
      return is_string($obj) ? trim(strip_tags($obj)) : $obj;
    }

    $back = '';
    foreach ($obj as $k => $v) {
      $back .= chr(10) . str_repeat(' ', $recursive * 2) . '     [' . strip_tags($k) . '] => (' . gettype($v) . ') ' . $this->parseObject($v, $recursive + 1);
    }
    return $back;
  }
}
