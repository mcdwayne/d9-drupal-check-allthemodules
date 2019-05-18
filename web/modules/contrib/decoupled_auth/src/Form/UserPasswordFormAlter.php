<?php

namespace Drupal\decoupled_auth\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form alter for the user password form.
 *
 * @see \Drupal\user\Form\UserPasswordForm
 */
class UserPasswordFormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(UserStorageInterface $user_storage, TranslationInterface $translation) {
    $this->userStorage = $user_storage;
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('string_translation')
    );
  }

  /**
   * Alter the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alter(array &$form, FormStateInterface $form_state) {
    // Replace the form validate with one aware of decoupled users.
    $this->replaceHandler($form['#validate'], '::validateForm', [$this, 'validateForm']);

    // Do the same for registration password's adjustment.
    $this->replaceHandler($form['#validate'], '_user_registrationpassword_user_pass_validate', [$this, 'validateFormRegistrationPassword']);
  }

  /**
   * Form validation handler that is aware of decoupled users.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\user\Form\UserPasswordForm::validateForm
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));

    // Get our base query.
    $base_query = $this->userStorage->getQuery()
      ->accessCheck(FALSE)
      ->range(0, 1);

    // Try to load by email.
    $query = clone $base_query;
    $users = $query
      ->condition('mail', $name)
      ->exists('name')
      ->execute();
    if (empty($users)) {
      // No success, try to load by name.
      $query = clone $base_query;
      $users = $query
        ->condition('name', $name)
        ->execute();
    }

    /* @var \Drupal\decoupled_auth\DecoupledAuthUserInterface $account */
    $account = !empty($users) ? $this->userStorage->load(reset($users)) : FALSE;
    if ($account && $account->id()) {
      // Blocked accounts cannot request a new password.
      if (!$account->isActive()) {
        $form_state->setErrorByName('name', $this->t('%name is blocked or has not been activated yet.', ['%name' => $name]));
      }
      else {
        $form_state->setValueForElement(['#parents' => ['account']], $account);
      }
    }
    else {
      $form_state->setErrorByName('name', $this->t('%name is not recognized as a username or an email address.', ['%name' => $name]));
    }
  }

  /**
   * Form validation handler that is aware of decoupled users.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see _user_registrationpassword_user_pass_validate
   */
  public function validateFormRegistrationPassword(array &$form, FormStateInterface $form_state) {
    // We try and load a blocked user that never ever logged in.
    // This should only return 'brand new' user accounts.
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = \Drupal::entityQuery('user')
      ->exists('name')
      ->condition('mail', $name)
      ->condition('status', 0)
      ->condition('access', 0)
      ->condition('login', 0)
      ->execute();

    if (empty($users)) {
      // No success, try to load by name.
      $users = \Drupal::entityQuery('user')
        ->condition('name', $name)
        ->condition('status', 0)
        ->condition('access', 0)
        ->condition('login', 0)
        ->execute();
    }

    if (!empty($users)) {
      $uid = reset($users);
      $account = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    }

    // If the account has never ever been used, load
    // the $account into the form for processing.
    if (!empty($account) && $account->id()) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
    // Otherwise, run validate of core override.
    else {
      $this->validateForm($form, $form_state);
    }
  }

  /**
   * Replace a handler, if it exists.
   *
   * @param array $handlers
   *   An array of handlers.
   * @param mixed $needle
   *   The callable to replace.
   * @param mixed $replacement
   *   The replacement callable.
   *
   * @return false|int
   *   The position of the handler, or FALSE if it did not exist.
   */
  protected function replaceHandler(array &$handlers, $needle, $replacement) {
    $pos = array_search($needle, $handlers);
    if ($pos !== FALSE) {
      $handlers[$pos] = $replacement;
    }
    return $pos;
  }

}
