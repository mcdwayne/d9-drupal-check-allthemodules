<?php

namespace Drupal\contacts\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event raised when confirmation is required for cancelling a user account.
 *
 * @package Drupal\contacts\Event
 */
class UserCancelConfirmationEvent extends Event {

  /**
   * Event for providing information on the cancel user confirmation form.
   */
  const NAME = 'contacts.user.cancel';

  /**
   * The user account being cancelled.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The name of the default group.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $defaultGroup;

  /**
   * Additional information for the cancellation form.
   *
   * Array of TranslatedMarkup instances, keyed by group.
   *
   * @var array
   */
  protected $info = [];

  /**
   * Additional confirmation steps for the cancellation form.
   *
   * Array of TranslatedMarkup instances, keyed by group.
   *
   * @var array
   */
  protected $confirmations = [];

  /**
   * Errors that should prevent cancellation for the account.
   *
   * Array of TranslatedMarkup instances, keyed by group.
   *
   * @var array
   */
  protected $errors = [];

  /**
   * Groups that exist for the event.
   *
   * Array with machine_name as key and TranslatableMarkup as value.
   *
   * @var array
   */
  protected $groups = [];

  /**
   * UserCancelConfirmationEvent constructor.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account being cancelled.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $default_group
   *   The name of the default group. Defaults to 'General'.
   */
  public function __construct(UserInterface $user, TranslatableMarkup $default_group = NULL) {
    $this->user = $user;
    $this->defaultGroup = $default_group ?? new TranslatableMarkup('General');
  }

  /**
   * Get the user account being cancelled.
   *
   * @return \Drupal\user\UserInterface
   *   The user account being cancelled.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Add information to the cancellation form.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The information text.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $group
   *   Optionally a group for categorising the information.
   */
  public function addInfo(TranslatableMarkup $message, TranslatableMarkup $group = NULL) {
    if ($group) {
      $key = $this->createMachineName($group);
    }
    else {
      $key = '_none';
      $group = $this->defaultGroup;
    }

    $this->groups[$key] = $group;
    $this->info[$key][$this->createMachineName($message)] = $message;
  }

  /**
   * Add a confirmation step to the cancellation form.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The confirmation text.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $group
   *   Optionally a group for categorising the confirmation step.
   */
  public function addConfirmation(TranslatableMarkup $message, TranslatableMarkup $group = NULL) {
    if ($group) {
      $key = $this->createMachineName($group);
    }
    else {
      $key = '_none';
      $group = $this->defaultGroup;
    }

    $this->groups[$key] = $group;
    $this->confirmations[$key][$this->createMachineName($message)] = $message;
  }

  /**
   * Add an error to the cancellation form. Prevents form submission.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The error message.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $group
   *   Optionally a group for categorising the error.
   */
  public function addError(TranslatableMarkup $message, TranslatableMarkup $group = NULL) {
    if ($group) {
      $key = $this->createMachineName($group);
    }
    else {
      $key = '_none';
      $group = $this->defaultGroup;
    }

    $this->groups[$key] = $group;
    $this->errors[$key][$this->createMachineName($message)] = $message;
  }

  /**
   * Whether any errors exist for this cancellation form.
   *
   * @return bool
   *   TRUE if errors exist. Otherwise FALSE.
   */
  public function hasError() {
    return !empty($this->errors);
  }

  /**
   * Get the groups of feedback that exist for this event.
   *
   * @return array
   *   The groups of feedback. Key is machine name and value is an instance of
   *   TranslatableMarkup.
   */
  public function getGroups() {
    return $this->groups;
  }

  /**
   * Get the information for a specific group machine name.
   *
   * @param string $group
   *   The machine name of a group to get information for.
   *
   * @return mixed|null
   *   The information as an array or NULL if none exists.
   */
  public function getInfo($group) {
    return $this->info[$group] ?? NULL;
  }

  /**
   * Get the confirmations for a specific group machine name.
   *
   * @param string $group
   *   The machine name of a group to get confirmations for.
   *
   * @return mixed|null
   *   The confirmations as an array or NULL if none exists.
   */
  public function getConfirmations($group) {
    return $this->confirmations[$group] ?? NULL;
  }

  /**
   * Get the errors for a specific group machine name.
   *
   * @param string $group
   *   The machine name of a group to get errors for.
   *
   * @return mixed|null
   *   The errors as an array or NULL if none exists.
   */
  public function getErrors($group) {
    return $this->errors[$group] ?? NULL;
  }

  /**
   * Create a machine name from TranslatableMarkup.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $name
   *   The TranslatableMarkup to create a machine name for.
   *
   * @return string
   *   A machine name version of the Markup.
   */
  protected function createMachineName(TranslatableMarkup $name) {
    return md5($name->jsonSerialize());
  }

}
