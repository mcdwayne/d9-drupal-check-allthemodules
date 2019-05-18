<?php

/**
 * Contains \Drupal\hooks\Event\HookEvent.
 */

namespace Drupal\hooks\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * This event represents a alter hook.
 */
class HookEvent extends Event {

  /**
   * The hook name.
   *
   * @var string
   */
  protected $hookName;

  /**
   * The hook data passed in by reference.
   *
   * @var mixed
   */
  protected $hookData;

  /**
   * The first context for this  hook.
   *
   * @var mixed|NULL
   */
  protected $context1;

  /**
   * The second context for this hook.
   *
   * @var mixed|NULL
   */
  protected $context2;

  /**
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::alter().
   */
  public function __construct($type, $data, $context1 = NULL, $context2 = NULL) {
    $this->hookName = $type;
    $this->hookData = $data;
    $this->context1 = $context1;
    $this->context2 = $context2;
  }

  /**
   * Gets the hook name.
   *
   * @return string
   *   The hook name this event wraps.
   */
  protected function getHookName() {
    return $this->hookName;
  }

  /**
   * Gets the hook data.
   * @return mixed
   *   The $data param for this alter hook.
   */
  public function getData() {
    return $this->hookData;
  }

  /**
   * Sets the hook data.
   *
   * @param mixed $data
   *   Overrides the $data param for this alter hook.
   */
  public function setData($data) {
    $this->hookData = $data;
  }

  /**
   * Gets the first context for this hook.
   *
   * @return mixed
   *   The hook context1.
   */
  public function getContext1() {
    return $this->context1;
  }

  /**
   * Sets the first context for this hook.
   *
   * @param mixed $context1
   *   Overrides context1 for this hook.
   */
  public function setContext1($context1) {
    $this->context1 = $context1;
  }


  /**
   * Gets the second context for this hook.
   *
   * @return mixed
   *   The hook context2.
   */
  public function getContext2() {
    return $this->context2;
  }

  /**
   * Sets the second context for this hook.
   *
   * @param mixed $context2
   *   Overrides context2 for this hook.
   */
  public function setContext2($context2) {
    $this->context2 = $context2;
  }

}
