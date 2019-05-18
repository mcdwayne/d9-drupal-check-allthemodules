<?php

namespace Drupal\past;

use Drupal\Core\Logger\RfcLogLevel;
use \Exception;

/**
 * Represents a logged event.
 */
interface PastEventInterface {

  /**
   * Returns the ID of the event, if saved.
   *
   * @return int
   *   The event ID.
   */
  public function id();

  /**
   * Sets the module of the event.
   *
   * @param string $module
   *   The name of the module which is logging the event.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setModule($module);

  /**
   * Set the machine name of this event.
   *
   * Has no special meaning other than providing an easy way to group and
   * identify past events.
   *
   * @param string $machine_name
   *   The machine name.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setMachineName($machine_name);

  /**
   *
   * @param string $message
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setMessage($message);

  /**
   * Returns the event message.
   *
   * @return string
   *   The logged event message. Contrary to watchdog, past event messages are
   *   not translated.
   */
  public function getMessage();

  /**
   * Returns the severity of this event.
   *
   * @return integer
   *   One of severities listed in RfcLogLevel::getLevels().
   */
  public function getSeverity();

  /**
   * Sets the severity of this event.
   *
   * @param integer $severity
   *   One of severities listed in RfcLogLevel::getLevels().
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setSeverity($severity);

  /**
   * Returns the session id of this event.
   *
   * @return string
   *   The session id.
   */
  public function getSessionId();

  /**
   * Sets the session id of this event.
   *
   * @param string $session_id
   *   The session id.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setSessionId($session_id);

  /**
   * Gets the URI that referred to the page where the event was logged.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function getReferer();

  /**
   * Sets the URI that referred to the page where the event was logged.
   *
   * @param string $referer
   *   The referer.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setReferer($referer);

  /**
   * Gets the URI of the page where the event was logged.
   *
   * @return string
   *   The location.
   */
  public function getLocation();

  /**
   * Sets the URI of the page where the event was logged.
   *
   * @param string $location
   *   The location.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setLocation($location);

  /**
   * Add an argument to the event.
   *
   * Each argument is identified by a key and has arbitrary data. The data is
   * normalized and persisted in a separate table so that it can be queried.
   *
   * @param string $key
   *   The name of the argument, must be unique for the event.
   * @param mixed $data
   *   The data that belongs to the argument and should be logged. Supported are
   *   scalar values, arrays, and objects. Exceptions are automatically decoded
   *   with Error::decodeException() including a backtrace.
   * @param array $options
   *   Options for the argument. Currently supported:
   *     - exclude: Array of keys/properties that should be ignored on the data
   *       structure and not logged.
   *
   * @return PastEventArgumentInterface
   *   The created past event argument object.
   */
  public function addArgument($key, $data, array $options = []);

  /**
   * Add an array of arguments to the event.
   *
   * The key for each will consists of '$key_prefix . $delimiter . $array_key'.
   *
   * @param string $key_prefix
   *   The name prefix of the arguments.
   * @param array $data
   *   Array of arguments.
   * @param array $options
   *   Options for the argument. Currently supported:
   *     - exclude: Array of keys/properties that should be ignored on the data
   *       structure and not logged.
   * @param string $delimiter
   *   The delimiter between the key prefix and the arguments with default
   *   value ':' as provided.
   *
   * @return array
   *   Array of the created past event argument objects.
   *
   * @see PastEventInterface::addArgument()
   */
  public function addArgumentArray($key_prefix, array $data, array $options = [], $delimiter = ':');

  /**
   * Returns all arguments of this event.
   *
   * @return PastEventArgumentInterface[]
   *   Array of all arguments of this event, keyed by key.
   */
  public function getArguments();

  /**
   * Returns a specific argument based on the key.
   *
   * @param string $key
   *   The key of the argument.
   *
   * @return PastEventArgumentInterface
   *   The argument object.
   */
  public function getArgument($key);

  /**
   * Adds an exception.
   *
   * @param Exception $exception
   *   The exception.
   * @param array $options
   *   Options for the argument. Currently supported:
   *     - exclude: Array of keys/properties that should be ignored on the data
   *       structure and not logged.
   * @param int $severity
   *   (optional) The severity of the event. Defaults to 'RfcLogLevel::ERROR'.
   *
   * @return PastEventArgumentInterface
   *   The created past event argument object.
   */
  public function addException(Exception $exception, array $options = [], $severity = RfcLogLevel::ERROR);

  /**
   * Allows to reference this to a parent event.
   *
   * @param int $event_id
   *   The event id of the event this belongs to.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setParentEventId($event_id);

  /**
   * Sets the event timestamp.
   *
   * @param int $timestamp
   *   The unix timestamp when this event happened.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setTimestamp($timestamp);

  /**
   * Sets Drupal user id.
   *
   * @param int $uid
   *   Drupal user id.
   *
   * @return PastEventInterface
   *   The current object.
   */
  public function setUid($uid);

  /**
   * Gets Drupal user id.
   *
   * @return int
   *   Drupal user id.
   */
  public function getUid();

  /**
   * Returns the timestamp when the event happened.
   *
   * @return int
   *   The timestamp when the event happened.
   */
  public function getTimestamp();

  /**
   * Returns the machine name of this event.
   *
   * @return string
   *   The machine name of this event.
   */
  public function getMachineName();

  /**
   * Returns the module that created this event.
   *
   * @return string
   *   The module name.
   */
  public function getModule();

  /**
   * Will flag the child event to use this as the parent once saved.
   *
   * @param int $event_id
   *   The event id that should use this event as the parent.
   */
  public function addChildEvent($event_id);

  /**
   * Saves the event.
   *
   * The event might not be saved, depending on the 'severity_threshold'
   * settings.
   *
   * @return int|null
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *   NULL if the event can not be saved.
   */
  public function save();
}
