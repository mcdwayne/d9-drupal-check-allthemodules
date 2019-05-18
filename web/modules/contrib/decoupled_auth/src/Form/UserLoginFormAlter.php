<?php

namespace Drupal\decoupled_auth\Form;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form alter for the user login form.
 *
 * @see \Drupal\user\Form\UserLoginForm
 */
class UserLoginFormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The email registration configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The email registration configuration.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(UserStorageInterface $user_storage, ImmutableConfig $config, TranslationInterface $translation) {
    $this->userStorage = $user_storage;
    $this->config = $config;
    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('config.factory')->get('email_registration.settings'),
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
    // Replace the email_registration name element validation handler with one
    // aware of decoupled users.
    if (isset($form['name']['#element_validate'])) {
      $this->replaceHandler($form['name']['#element_validate'], 'email_registration_user_login_validate', [$this, 'nameElementValidate']);
    }
  }

  /**
   * Element validation handler that is aware of decoupled users.
   *
   * @param array $element
   *   An associative array containing the structure of the element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\user\Form\UserPasswordForm::validateForm
   */
  public function nameElementValidate(array &$element, FormStateInterface $form_state) {
    $mail = $form_state->getValue('name');
    if (!empty($mail)) {
      $query = $this->userStorage->getQuery()
        ->exists('name')
        ->range(0, 1);
      $condition = $query->orConditionGroup();
      $condition->condition('mail', $mail);
      if ($this->config->get('login_with_username')) {
        $condition->condition('name', $mail);
      }
      $query->condition($condition);
      $users = $query->execute();
      if (!empty($users) && $user = $this->userStorage->load(reset($users))) {
        $form_state->setValue('name', $user->getAccountName());
      }
      else {
        $user_input = $form_state->getUserInput();
        $query = isset($user_input['name']) ? ['name' => $user_input['name']] : [];
        $params = [
          ':password' => Url::fromRoute('user.pass', [], ['query' => $query])->toString(),
        ];
        $form_state->setErrorByName('name', $this->t('Unrecognized e-mail address or password. <a href=":password">Forgot your password?</a>', $params));
      }
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
