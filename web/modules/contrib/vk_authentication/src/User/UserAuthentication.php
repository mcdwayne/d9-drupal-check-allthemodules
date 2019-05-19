<?php

namespace Drupal\vk_authentication\User;

use Drupal\user\Entity\User;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class UserAuthentication.
 *
 * @package Drupal\vk_authentication\User
 */
class UserAuthentication extends UserAdditional {

  // Wrapper methods for \Drupal\Core\StringTranslation\TranslationInterface.
  use StringTranslationTrait;

  /**
   * The Drupal messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * The Drupal language_manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $logger;

  /**
   * UserAuthentication constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   Service "string_translation" as parameter.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language_manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(MessengerInterface $messenger,
                              LoggerChannelFactory $logger,
                              TranslationInterface $stringTranslation,
                              LanguageManagerInterface $languageManager,
                              EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->stringTranslation = $stringTranslation;
    $this->languageManager = $languageManager;
    $this->messenger = $messenger;
    $this->logger = $logger;
  }

  /**
   * Checking user by his email.
   *
   * @param string|bool $userEmail
   *   User email.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User
   *   Return User object if authentication succeed, or boolean otherwise.
   *
   * @throws \Exception
   */
  public function userCheck($userEmail) {

    // If user hasn't email.
    if (!$userEmail) {
      return $this->userHasNoEmail();
    }
    // Try to load user by email.
    $user = $this->loadUserByEmail($userEmail);
    // Create new user.
    if (!$user) {
      return $this->userCreate($userEmail);
    }
    // Check if user banned.
    elseif (!$user->isActive()) {
      return $this->userBanned($userEmail);
    }
    else {
      // Log in user.
      return $this->userLogin($user, $userEmail);
    }

  }

  /**
   * Create new user.
   *
   * @param string $userEmail
   *   Create a new user account using provided email.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\user\Entity\User
   *   Return created user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function userCreate(string $userEmail) {
    $lang = $this->languageManager->getCurrentLanguage()->getId();
    $user = User::create();

    $userPassword = bin2hex(openssl_random_pseudo_bytes(5));

    $user->setUsername($userEmail);
    $user->setPassword($userPassword);
    $user->setEmail($userEmail);
    // Set this to FALSE if you want to edit (re save) an existing user object.
    $user->enforceIsNew();

    $user->set("langcode", $lang);
    $user->set("preferred_langcode", $lang);

    $user->activate();
    $user->save();

    user_login_finalize($user);

    $this->makeMessage(1, $userEmail, $userPassword);

    return $user;
  }

  /**
   * Loading user by his email.
   *
   * @param string $userEmail
   *   User email.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed
   *   Return User object if succeed, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadUserByEmail(string $userEmail) {
    $users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['mail' => $userEmail]);

    return $users ? reset($users) : FALSE;
  }

  /**
   * Log in user.
   *
   * @param \Drupal\user\Entity\User $user
   *   User object.
   * @param string $userEmail
   *   User email.
   *
   * @return mixed
   *   Return user object or boolean
   */
  private function userLogin(User $user, string $userEmail) {
    user_login_finalize($user);

    $this->makeMessage(2, $userEmail, NULL);

    return $user;
  }

  /**
   * If user banned.
   *
   * @param string $userEmail
   *   User email.
   *
   * @return bool
   *   Return boolean.
   */
  private function userBanned($userEmail) {
    return $this->makeMessage(3, $userEmail, NULL);
  }

  /**
   * If user has no email linked to vk social network account.
   *
   * @return bool
   *   Return boolean.
   */
  private function userHasNoEmail() {
    return $this->makeMessage(4, NULL, NULL);
  }

  /**
   * Creating message for user.
   *
   * @param int $code
   *   Message code.
   * @param mixed $email
   *   User email.
   * @param mixed $password
   *   User password.
   *
   * @return bool
   *   Return boolean.
   */
  private function makeMessage($code, $email, $password) {
    switch ($code) {

      case 1:
        $this->messenger->addMessage(
            $this->t('<h3>You have successfully signed up on the site.</h3><br> 
                                It was created an account, where your name - is your email address <strong>@email</strong>, linked to your social network VK personal page.<br> 
                                <br>Password of your account is: <strong>@password</strong>. <br>You can change your password any time.', ['@email' => $email, '@password' => $password]));
        $this->logger->get('Vk_authentication')->notice($email . ' user registered.');
        break;

      case 2:
        $this->messenger->addMessage(
            $this->t('You have successfully logged on the site as user <strong>@email</strong>.', ['@email' => $email]));
        $this->logger->get('Vk_authentication')->notice($email . ' user has been logged.');
        break;

      case 3:
        $this->messenger->addError(
             $this->t('Your account was blocked by administrator. Please, contact him to get details.'));
        $this->logger->get('Vk_authentication')->notice($email . ' user blocked and try to log in');
        break;

      case 4:
        $this->messenger->addWarning(
            $this->t('Can not get your email. Maybee you have not linked your email address to your social network personal page.
                                Without email address impossible to log in or sign up'));
        $this->logger->get('Vk_authentication')->notice('user try to log in, but has no email linked to the social network account.');
        break;

      default:
        $this->messenger->addMessage(
            $this->t('Default message'));
    }

    return TRUE;
  }

}
