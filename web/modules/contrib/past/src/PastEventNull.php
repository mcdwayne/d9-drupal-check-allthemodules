<?php

namespace Drupal\past;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Null implementation that is used as a fallback or when logging is disabled.
 */
class PastEventNull implements PastEventInterface {

  /**
   * {@inheritdoc}
   */
  public function addArgument($key, $data, array $options = []) {
    return $this->getArgument(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function addArgumentArray($key_prefix, array $data, array $options = [], $delimiter = ':') {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument($key) {
    return new PastEventArgumentNull();
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function addException(\Exception $exception, array $options = [], $severity = RfcLogLevel::ERROR) {

  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return RfcLogLevel::INFO;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferer() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return time();
  }

  /**
   * {@inheritdoc}
   */
  public function getUid() {
    return -1;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return -1;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEventId($event_id) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverity($severity) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($session_id) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReferer($referer) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($machine_name) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setModule($module) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addChildEvent($event_id) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildEvents() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {

  }
}
